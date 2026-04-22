import type { CSSProperties, PointerEvent as ReactPointerEvent } from 'react';
import { startTransition, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import styles from './styles.css?inline';

// ─── Helpers ─────────────────────────────────────────────────────────

/** uid() requires a secure context (HTTPS). Fall back for local dev over HTTP. */
const uid = (): string =>
  typeof crypto.randomUUID === 'function'
    ? crypto.randomUUID()
    : `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;

const AI_WIDGET_OPEN_STORAGE_KEY = 'skills-chat-widget:is-open';
const AI_WIDGET_SIZE_STORAGE_KEY = 'skills-chat-widget:size';
const MOBILE_BREAKPOINT = 640;
const DEFAULT_PANEL_WIDTH = 420;
const DEFAULT_PANEL_HEIGHT = 720;
const MIN_PANEL_WIDTH = 320;
const MIN_PANEL_HEIGHT = 360;

type PanelSize = {
  width: number;
  height: number;
};

type ResizeDirection = 'top' | 'left';

type ResizeSession = {
  direction: ResizeDirection;
  startX: number;
  startY: number;
  startWidth: number;
  startHeight: number;
  previousCursor: string;
  previousUserSelect: string;
};

// ─── Types ───────────────────────────────────────────────────────────

type ToolStatus = 'running' | 'complete' | 'error';

type ToolEvent = {
  id: string;
  kind: 'tool';
  name: string;
  input: string;
  result: string;
  status: ToolStatus;
};

type ThinkingEvent = {
  id: string;
  kind: 'thinking';
};

type MessageEvent = {
  id: string;
  kind: 'message';
  role: 'user' | 'assistant';
  text: string;
  streaming?: boolean;
};

type TimelineEvent = ToolEvent | ThinkingEvent | MessageEvent;

/**
 * Internal message format shared with the PHP backend.
 * This is the "source of truth" for conversation history — the timeline
 * is purely a display concern derived from it.
 */
type InternalMessage =
  | { role: 'user'; content: string }
  | { role: 'assistant'; content: string; toolCalls?: ToolCall[] }
  | { role: 'tool'; toolCallId: string; name: string; content: string };

type ToolCall = {
  id: string;
  name: string;
  input: Record<string, unknown>;
};

type PageContext = {
  surface?: 'cp' | 'site';
  currentUrl?: string;
  controlPanelUrl?: string;
  requestPath?: string;
  requestedRoute?: string;
  routeParams?: Record<string, unknown>;
  elementId?: number;
  elementType?: string;
  elementTitle?: string;
  elementSlug?: string;
  elementUri?: string;
  siteId?: number;
};

function formatToolPayload(
  value: unknown,
  options: {
    empty: string;
    maxLength?: number;
  },
): string {
  const text = typeof value === 'string'
    ? value
    : typeof value === 'object' && value !== null
      ? JSON.stringify(value, null, 2)
      : value == null
        ? ''
        : String(value);

  const normalized = text.trim();

  if (!normalized) {
    return options.empty;
  }

  if (options.maxLength && normalized.length > options.maxLength) {
    return `${normalized.slice(0, options.maxLength)}...`;
  }

  return normalized;
}

function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), max);
}

function parseUrl(value: string | undefined, base?: string): URL | null {
  if (!value) {
    return null;
  }

  try {
    return new URL(value, base ?? window.location.href);
  } catch {
    return null;
  }
}

function isControlPanelUrl(url: URL, pageContext: PageContext): boolean {
  const controlPanelUrl = parseUrl(pageContext.controlPanelUrl, window.location.href);

  if (!controlPanelUrl) {
    return false;
  }

  const controlPanelPath = controlPanelUrl.pathname.replace(/\/$/, '');

  return url.origin === controlPanelUrl.origin
    && (url.pathname === controlPanelPath || url.pathname.startsWith(`${controlPanelPath}/`));
}

function shouldRedirectForSurface(context: 'cp' | 'site', targetUrl: URL, pageContext: PageContext): boolean {
  if (!['http:', 'https:'].includes(targetUrl.protocol)) {
    return false;
  }

  if (context === 'cp') {
    return isControlPanelUrl(targetUrl, pageContext);
  }

  return targetUrl.origin === window.location.origin && !isControlPanelUrl(targetUrl, pageContext);
}

function maybeRedirectForTool(name: string, result: unknown, context: 'cp' | 'site', pageContext: PageContext): void {
  if (name !== 'OpenUrl') {
    return;
  }

  const urlValue = (result as Record<string, unknown> | null)?.url;

  if (typeof urlValue !== 'string') {
    return;
  }

  const targetUrl = parseUrl(urlValue);

  if (!targetUrl || !shouldRedirectForSurface(context, targetUrl, pageContext)) {
    return;
  }

  window.location.assign(targetUrl.toString());
}

function getPanelBounds(): PanelSize {
  if (typeof window === 'undefined') {
    return {
      width: DEFAULT_PANEL_WIDTH,
      height: DEFAULT_PANEL_HEIGHT,
    };
  }

  return {
    width: Math.max(MIN_PANEL_WIDTH, window.innerWidth - 24),
    height: Math.max(
      MIN_PANEL_HEIGHT,
      window.innerWidth <= MOBILE_BREAKPOINT ? window.innerHeight - 88 : window.innerHeight - 110,
    ),
  };
}

function normalizePanelSize(size: Partial<PanelSize> | null | undefined): PanelSize {
  const bounds = getPanelBounds();

  return {
    width: clamp(
      Math.round(typeof size?.width === 'number' && Number.isFinite(size.width) ? size.width : DEFAULT_PANEL_WIDTH),
      MIN_PANEL_WIDTH,
      bounds.width,
    ),
    height: clamp(
      Math.round(
        typeof size?.height === 'number' && Number.isFinite(size.height)
          ? size.height
          : DEFAULT_PANEL_HEIGHT,
      ),
      MIN_PANEL_HEIGHT,
      bounds.height,
    ),
  };
}

// ─── SSE reader ──────────────────────────────────────────────────────

type SseCallback = {
  onText: (content: string) => void;
  onTurn: (id: string) => void;
  onToolStart: (id: string, name: string, input: Record<string, unknown>) => void;
  onToolEnd: (id: string, name: string, result: unknown, isError: boolean) => void;
  onDone: (newMessages: InternalMessage[]) => void;
  onError: (message: string) => void;
};

async function streamChat(
  url: string,
  messages: InternalMessage[],
  userMessage: string,
  pageContext: PageContext,
  callbacks: SseCallback,
) {
  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ messages, message: userMessage, pageContext }),
  });

  if (!response.ok) {
    const text = await response.text();
    let errorMsg: string;

    try {
      const json = JSON.parse(text);
      errorMsg = json.error ?? `HTTP ${response.status}`;
    } catch {
      errorMsg = `HTTP ${response.status}: ${text.slice(0, 200)}`;
    }

    callbacks.onError(errorMsg);
    return;
  }

  const reader = response.body?.getReader();

  if (!reader) {
    callbacks.onError('No readable stream in response.');
    return;
  }

  const decoder = new TextDecoder();
  let buffer = '';

  while (true) {
    const { done, value } = await reader.read();

    if (done) {
      break;
    }

    buffer += decoder.decode(value, { stream: true });

    // Process complete SSE frames (double newline delimited)
    let boundary: number;

    while ((boundary = buffer.indexOf('\n\n')) !== -1) {
      const frame = buffer.slice(0, boundary);
      buffer = buffer.slice(boundary + 2);

      let eventType = '';
      let dataStr = '';

      for (const line of frame.split('\n')) {
        if (line.startsWith('event: ')) {
          eventType = line.slice(7);
        } else if (line.startsWith('data: ')) {
          dataStr += line.slice(6);
        }
      }

      if (!dataStr) {
        continue;
      }

      let data: Record<string, unknown>;

      try {
        data = JSON.parse(dataStr);
      } catch {
        continue;
      }

      switch (eventType) {
        case 'turn':
          callbacks.onTurn(data.id as string);
          break;

        case 'text':
          callbacks.onText(data.content as string);
          break;

        case 'tool_start':
          callbacks.onToolStart(
            data.id as string,
            data.name as string,
            (data.input as Record<string, unknown>) ?? {},
          );
          break;

        case 'tool_end':
          callbacks.onToolEnd(
            data.id as string,
            data.name as string,
            data.result,
            data.isError === true,
          );
          break;

        case 'done':
          callbacks.onDone((data.messages as InternalMessage[]) ?? []);
          break;

        case 'error':
          callbacks.onError(data.message as string);
          break;
      }
    }
  }
}

// ─── App ─────────────────────────────────────────────────────────────

const welcomeTimeline: TimelineEvent[] = [
  {
    id: 'welcome',
    kind: 'message',
    role: 'assistant',
    text: 'Hi! I\'m your Craft CMS assistant. Ask me to create entries, search content, manage fields, or anything else. I have full access to your Craft installation.',
  },
];

type AppProps = {
  chatUrl: string;
  canChat: boolean;
  configured: boolean;
  context: 'cp' | 'site';
  pageContext: PageContext;
};

function App({ chatUrl, canChat, configured, context, pageContext }: AppProps) {
  const [timeline, setTimeline] = useState<TimelineEvent[]>(welcomeTimeline);
  const [history, setHistory] = useState<InternalMessage[]>([]);
  const [prompt, setPrompt] = useState('');
  const [isStreaming, setIsStreaming] = useState(false);
  const [isOpen, setIsOpen] = useState(() => {
    if (typeof window === 'undefined') {
      return false;
    }

    return window.localStorage.getItem(AI_WIDGET_OPEN_STORAGE_KEY) === 'true';
  });
  const [panelSize, setPanelSize] = useState<PanelSize>(() => {
    if (typeof window === 'undefined') {
      return {
        width: DEFAULT_PANEL_WIDTH,
        height: DEFAULT_PANEL_HEIGHT,
      };
    }

    try {
      const stored = window.localStorage.getItem(AI_WIDGET_SIZE_STORAGE_KEY);

      if (!stored) {
        return normalizePanelSize(null);
      }

      return normalizePanelSize(JSON.parse(stored) as Partial<PanelSize>);
    } catch {
      return normalizePanelSize(null);
    }
  });
  const conversationRef = useRef<HTMLDivElement | null>(null);
  const currentTurnId = useRef<string | null>(null);
  const currentThinkingId = useRef<string | null>(null);
  const currentAssistantId = useRef<string | null>(null);
  const promptId = useRef(uid());
  const resizeSessionRef = useRef<ResizeSession | null>(null);
  const isCompactViewport = typeof window !== 'undefined' && window.innerWidth <= MOBILE_BREAKPOINT;

  // Auto-scroll to bottom on new content
  useEffect(() => {
    const el = conversationRef.current;

    if (el) {
      el.scrollTop = el.scrollHeight;
    }
  }, [timeline]);

  useEffect(() => {
    window.localStorage.setItem(AI_WIDGET_OPEN_STORAGE_KEY, String(isOpen));
  }, [isOpen]);

  useEffect(() => {
    window.localStorage.setItem(AI_WIDGET_SIZE_STORAGE_KEY, JSON.stringify(panelSize));
  }, [panelSize]);

  const stopResizing = useCallback(() => {
    const session = resizeSessionRef.current;

    if (!session) {
      return;
    }

    document.body.style.cursor = session.previousCursor;
    document.body.style.userSelect = session.previousUserSelect;
    resizeSessionRef.current = null;
  }, []);

  useEffect(() => {
    const handlePointerMove = (event: PointerEvent) => {
      const session = resizeSessionRef.current;

      if (!session) {
        return;
      }

      const bounds = getPanelBounds();

      if (session.direction === 'left') {
        const width = clamp(
          session.startWidth - (event.clientX - session.startX),
          MIN_PANEL_WIDTH,
          bounds.width,
        );

        setPanelSize((prev) => (prev.width === width ? prev : { ...prev, width }));
        return;
      }

      const height = clamp(
        session.startHeight - (event.clientY - session.startY),
        MIN_PANEL_HEIGHT,
        bounds.height,
      );

      setPanelSize((prev) => (prev.height === height ? prev : { ...prev, height }));
    };

    const handleViewportResize = () => {
      setPanelSize((prev) => normalizePanelSize(prev));
    };

    window.addEventListener('pointermove', handlePointerMove);
    window.addEventListener('pointerup', stopResizing);
    window.addEventListener('pointercancel', stopResizing);
    window.addEventListener('resize', handleViewportResize);

    return () => {
      window.removeEventListener('pointermove', handlePointerMove);
      window.removeEventListener('pointerup', stopResizing);
      window.removeEventListener('pointercancel', stopResizing);
      window.removeEventListener('resize', handleViewportResize);
      stopResizing();
    };
  }, [stopResizing]);

  const handleResizeStart = useCallback(
    (direction: ResizeDirection) => (event: ReactPointerEvent<HTMLDivElement>) => {
      if (window.innerWidth <= MOBILE_BREAKPOINT) {
        return;
      }

      event.preventDefault();

      resizeSessionRef.current = {
        direction,
        startX: event.clientX,
        startY: event.clientY,
        startWidth: panelSize.width,
        startHeight: panelSize.height,
        previousCursor: document.body.style.cursor,
        previousUserSelect: document.body.style.userSelect,
      };

      document.body.style.cursor = direction === 'left' ? 'ew-resize' : 'ns-resize';
      document.body.style.userSelect = 'none';
    },
    [panelSize.height, panelSize.width],
  );

  const panelStyle = useMemo<CSSProperties | undefined>(() => {
    if (isCompactViewport) {
      return undefined;
    }

    return {
      width: `${panelSize.width}px`,
      height: `${panelSize.height}px`,
    };
  }, [isCompactViewport, panelSize.height, panelSize.width]);

  const statusLabel = useMemo(() => {
    if (!configured) {
      return 'LLM not configured';
    }

    if (!canChat) {
      return context === 'cp' ? 'Sign in required' : 'CP login required';
    }

    return context === 'cp' ? 'Control Panel' : '';
  }, [canChat, configured, context]);

  const handleSubmit = useCallback(
    () => {
      const text = prompt.trim();

      if (!text || isStreaming || !canChat || !configured) {
        return;
      }

      setPrompt('');
      setIsStreaming(true);

      // Add user message to timeline
      const userMsgId = uid();

      setTimeline((prev) => [
        ...prev,
        { id: userMsgId, kind: 'message', role: 'user', text },
      ]);

      currentTurnId.current = null;
      currentThinkingId.current = null;
      currentAssistantId.current = null;

      streamChat(chatUrl, history, text, pageContext, {
        onTurn(id) {
          const previousThinkingId = currentThinkingId.current;
          const thinkingId = `thinking-${id}`;

          currentTurnId.current = id;
          currentThinkingId.current = thinkingId;
          currentAssistantId.current = null;

          startTransition(() => {
            setTimeline((prev) => [
              ...prev.filter((e) => e.id !== previousThinkingId),
              { id: thinkingId, kind: 'thinking' },
            ]);
          });
        },

        onText(content) {
          const targetId = currentAssistantId.current;
          const turnId = currentTurnId.current;

          if (targetId) {
            startTransition(() => {
              setTimeline((prev) =>
                prev.map((e) =>
                  e.id === targetId && e.kind === 'message'
                    ? { ...e, text: e.text + content }
                    : e,
                ),
              );
            });

            return;
          }

          if (!turnId) {
            return;
          }

          const thinkingId = currentThinkingId.current;
          const assistantId = `assistant-${turnId}`;

          currentThinkingId.current = null;
          currentAssistantId.current = assistantId;

          startTransition(() => {
            setTimeline((prev) => [
              ...prev.filter((e) => e.id !== thinkingId),
              {
                id: assistantId,
                kind: 'message',
                role: 'assistant',
                text: content,
                streaming: true,
              },
            ]);
          });
        },

        onToolStart(id, name, input) {
          const thinkingId = currentThinkingId.current;

          currentThinkingId.current = null;

          startTransition(() => {
            setTimeline((prev) => [
              ...prev.filter((e) => e.id !== thinkingId),
              {
                id: `tool-${id}`,
                kind: 'tool',
                name,
                input: formatToolPayload(input, { empty: 'No input', maxLength: 280 }),
                result: 'Running...',
                status: 'running',
              },
            ]);
          });
        },

        onToolEnd(id, name, result, isError) {
          startTransition(() => {
            setTimeline((prev) =>
              prev.map((e) =>
                e.id === `tool-${id}` && e.kind === 'tool'
                  ? {
                      ...e,
                      result: formatToolPayload(result, {
                        empty: 'No result',
                        maxLength: 1000,
                      }),
                      status: isError ? 'error' : 'complete',
                    }
                  : e,
              ),
            );
          });

          maybeRedirectForTool(name, result, context, pageContext);
        },

        onDone(newMessages) {
          // Mark all assistant bubbles as done streaming
          const thinkingId = currentThinkingId.current;

          currentTurnId.current = null;
          currentThinkingId.current = null;
          currentAssistantId.current = null;

          startTransition(() => {
            setTimeline((prev) =>
              prev
                .filter((e) => e.id !== thinkingId)
                .map((e) =>
                  e.kind === 'message' && e.streaming ? { ...e, streaming: false } : e,
                ),
            );
          });

          // Append the user message + new LLM messages to the internal history
          setHistory((prev) => [
            ...prev,
            { role: 'user', content: text } as InternalMessage,
            ...newMessages,
          ]);

          setIsStreaming(false);
        },

        onError(message) {
          const thinkingId = currentThinkingId.current;

          currentTurnId.current = null;
          currentThinkingId.current = null;
          currentAssistantId.current = null;

          startTransition(() => {
            setTimeline((prev) => {
              // Mark streaming messages as done
              const updated = prev
                .filter((e) => e.id !== thinkingId)
                .map((e) => (e.kind === 'message' && e.streaming ? { ...e, streaming: false } : e));

              return [
                ...updated,
                {
                  id: uid(),
                  kind: 'message' as const,
                  role: 'assistant' as const,
                   text: `Error: ${message}`,
                 },
               ];
             });
          });

          setIsStreaming(false);
        },
      }).catch((err) => {
        const thinkingId = currentThinkingId.current;

        currentTurnId.current = null;
        currentThinkingId.current = null;
        currentAssistantId.current = null;

        startTransition(() => {
          setTimeline((prev) => [
            ...prev.filter((e) => e.id !== thinkingId),
            {
              id: uid(),
              kind: 'message',
              role: 'assistant',
               text: `Network error: ${err instanceof Error ? err.message : String(err)}`,
             },
           ]);
         });

        setIsStreaming(false);
      });
    },
    [prompt, isStreaming, history, chatUrl, pageContext, canChat, configured, context],
  );

  return (
    <section className={`skills-chat-widget${isOpen ? ' skills-chat-widget--open' : ''}`}>
      <button
        aria-controls="skills-chat-panel"
        aria-expanded={isOpen}
        className="skills-chat-launcher"
        onClick={() => setIsOpen((open) => !open)}
        type="button"
      >
        <span className="skills-chat-launcherLabel">AI</span>
        {statusLabel ? <span className="skills-chat-launcherMeta">{statusLabel}</span> : null}
      </button>

      {isOpen ? (
        <div
          className="skills-chat-panel"
          id="skills-chat-panel"
          style={panelStyle}
        >
          <div
            aria-hidden="true"
            className="skills-chat-resizeHandle skills-chat-resizeHandle--top"
            onPointerDown={handleResizeStart('top')}
          />
          <div
            aria-hidden="true"
            className="skills-chat-resizeHandle skills-chat-resizeHandle--left"
            onPointerDown={handleResizeStart('left')}
          />
          <div className="skills-chat-frame">
            <div className="skills-chat-conversation" ref={conversationRef}>
              {timeline.map((entry) =>
                entry.kind === 'tool' ? (
                  <article className="skills-tool-card" key={entry.id}>
                    <details className="skills-tool-disclosure">
                      <summary className="skills-tool-summary">
                        <div className="skills-tool-row">
                          <strong>{entry.name}</strong>
                          <span className={`skills-tool-status skills-tool-status--${entry.status}`}>
                            {entry.status}
                          </span>
                        </div>
                      </summary>
                      <div className="skills-tool-body">
                        <div className="skills-tool-section">
                          <div className="skills-tool-label">Input</div>
                          <pre className="skills-tool-detail skills-tool-detail--input">{entry.input}</pre>
                        </div>
                        <div className="skills-tool-section">
                          <div className="skills-tool-label">Output</div>
                          <pre className="skills-tool-detail">{entry.result}</pre>
                        </div>
                      </div>
                    </details>
                  </article>
                ) : entry.kind === 'thinking' ? (
                  <article className="skills-message skills-message--thinking" key={entry.id}>
                    <div className="skills-message-bubble skills-message-bubble--thinking">
                      Assistant is thinking
                      <span aria-hidden="true" className="skills-thinking-dots">
                        <span />
                        <span />
                        <span />
                      </span>
                    </div>
                  </article>
                ) : (
                  <article
                    className={`skills-message skills-message--${entry.role}`}
                    key={entry.id}
                  >
                    <div className="skills-message-role">{entry.role}</div>
                    <div className="skills-message-bubble">
                      {entry.role === 'assistant' ? (
                        <ReactMarkdown
                          components={{
                            a: ({ ...props }) => (
                              <a {...props} rel="noreferrer noopener" target="_blank" />
                            ),
                          }}
                          remarkPlugins={[remarkGfm]}
                        >
                          {entry.text || (entry.streaming ? ' ' : '')}
                        </ReactMarkdown>
                      ) : (
                        entry.text || (entry.streaming ? ' ' : null)
                      )}
                      {entry.streaming ? <span className="skills-cursor" /> : null}
                    </div>
                  </article>
                ),
              )}
            </div>

            {configured && canChat ? (
              <div className="skills-chat-composer">
                <label className="visually-hidden" htmlFor={promptId.current}>
                  Prompt
                </label>
                <textarea
                  id={promptId.current}
                  onChange={(e) => setPrompt(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                      e.preventDefault();
                      handleSubmit();
                    }
                  }}
                  placeholder="Ask me to create content, search entries, or manage fields..."
                  rows={3}
                  value={prompt}
                />

                <div className="skills-chat-composerFooter">
                  <span>Enter to send, Shift+Enter for a new line.</span>
                  <button
                    disabled={isStreaming || prompt.trim().length === 0}
                    onClick={handleSubmit}
                    type="button"
                  >
                    {isStreaming ? 'Thinking...' : 'Send'}
                  </button>
                </div>
              </div>
            ) : (
              <div className="skills-chat-stateNotice" role="status">
                {configured
                  ? 'Sign into the Craft control panel to use the assistant from this page.'
                  : 'Configure an LLM provider in config/ai.php before using the assistant.'}
              </div>
            )}
          </div>
        </div>
      ) : null}
    </section>
  );
}

class CraftSkillChatElement extends HTMLElement {
  private root: ReturnType<typeof createRoot> | null = null;

  private pageContext(): PageContext {
    const raw = this.dataset.pageContext;

    if (!raw) {
      return { currentUrl: window.location.href };
    }

    try {
      const parsed = JSON.parse(raw) as PageContext;

      return {
        ...parsed,
        currentUrl: parsed.currentUrl ?? window.location.href,
      };
    } catch {
      return { currentUrl: window.location.href };
    }
  }

  connectedCallback() {
    if (this.root) {
      return;
    }

    const shadowRoot = this.shadowRoot ?? this.attachShadow({ mode: 'open' });
    const mountPoint = document.createElement('div');
    const styleTag = document.createElement('style');

    styleTag.textContent = styles;
    shadowRoot.replaceChildren(styleTag, mountPoint);

    this.root = createRoot(mountPoint);
    this.root.render(
      <App
        canChat={this.dataset.canChat === '1'}
        chatUrl={this.dataset.chatUrl ?? ''}
        configured={this.dataset.configured === '1'}
        context={this.dataset.context === 'cp' ? 'cp' : 'site'}
        pageContext={this.pageContext()}
      />,
    );
  }

  disconnectedCallback() {
    this.root?.unmount();
    this.root = null;
  }
}

if (!customElements.get('craft-skill-chat')) {
  customElements.define('craft-skill-chat', CraftSkillChatElement);
}
