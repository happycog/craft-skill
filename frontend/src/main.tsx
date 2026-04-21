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

// ─── SSE reader ──────────────────────────────────────────────────────

type SseCallback = {
  onText: (content: string) => void;
  onTurn: (id: string) => void;
  onToolStart: (id: string, name: string, input: Record<string, unknown>) => void;
  onToolEnd: (id: string, name: string, result: unknown) => void;
  onDone: (newMessages: InternalMessage[]) => void;
  onError: (message: string) => void;
};

async function streamChat(
  url: string,
  messages: InternalMessage[],
  userMessage: string,
  currentUrl: string,
  callbacks: SseCallback,
) {
  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ messages, message: userMessage, currentUrl }),
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
  currentUrl: string;
};

function App({ chatUrl, canChat, configured, context, currentUrl }: AppProps) {
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
  const conversationRef = useRef<HTMLDivElement | null>(null);
  const currentTurnId = useRef<string | null>(null);
  const currentThinkingId = useRef<string | null>(null);
  const currentAssistantId = useRef<string | null>(null);
  const promptId = useRef(uid());

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

  const chatSummary = useMemo(() => {
    const count = timeline.filter((e) => e.kind === 'message').length;

    return `${count} message${count === 1 ? '' : 's'}`;
  }, [timeline]);

  const statusLabel = useMemo(() => {
    if (!configured) {
      return 'LLM not configured';
    }

    if (!canChat) {
      return context === 'cp' ? 'Sign in required' : 'CP login required';
    }

    return context === 'cp' ? 'Control Panel' : 'Front-end';
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

      streamChat(chatUrl, history, text, currentUrl, {
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

        onToolEnd(id, _name, result) {
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
                      status: (result as Record<string, unknown>)?.error ? 'error' : 'complete',
                    }
                  : e,
              ),
            );
          });
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
    [prompt, isStreaming, history, chatUrl, currentUrl, canChat, configured],
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
        <span className="skills-chat-launcherMeta">{statusLabel}</span>
      </button>

      {isOpen ? (
        <div className="skills-chat-panel" id="skills-chat-panel">
          <header className="skills-chat-panelHeader">
            <div>
              <p className="skills-chat-eyebrow">Craft Skill</p>
              <h2>Chat</h2>
            </div>

            <div className="skills-chat-meta">
              <span>{context === 'cp' ? 'CP page' : 'Front-end page'}</span>
              <span>{chatSummary}</span>
            </div>
          </header>

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
                        <pre className="skills-tool-detail skills-tool-detail--input">{entry.input}</pre>
                      </summary>
                      <pre className="skills-tool-detail">{entry.result}</pre>
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
        currentUrl={this.dataset.currentUrl ?? window.location.href}
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
