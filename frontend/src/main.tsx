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
  template?: string;
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

type ElementRect = {
  top: number;
  left: number;
  width: number;
  height: number;
};

type TargetSelection = {
  element: HTMLElement;
  path: string;
  text: string;
  rect: ElementRect;
};

type TargetPopoverPosition = {
  top: number;
  left: number;
  width: number;
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

function getElementRect(element: HTMLElement): ElementRect {
  const rect = element.getBoundingClientRect();

  return {
    top: rect.top,
    left: rect.left,
    width: rect.width,
    height: rect.height,
  };
}

function isVisibleBlockElement(element: HTMLElement): boolean {
  const style = window.getComputedStyle(element);
  const display = style.display;
  const rect = element.getBoundingClientRect();

  if (
    style.visibility === 'hidden'
    || style.pointerEvents === 'none'
    || display === 'none'
    || display === 'contents'
    || display.startsWith('inline')
  ) {
    return false;
  }

  return rect.width >= 8 && rect.height >= 8;
}

function normalizeElementText(element: HTMLElement): string {
  const text = (element.innerText || element.textContent || '').replace(/\s+/g, ' ').trim();

  if (!text) {
    return '[No text content]';
  }

  return text.length > 500 ? `${text.slice(0, 497)}...` : text;
}

function escapeCssIdentifier(value: string): string {
  if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
    return CSS.escape(value);
  }

  return value.replace(/([^a-zA-Z0-9_-])/g, '\\$1');
}

function formatElementSegment(element: HTMLElement): string {
  const tag = element.tagName.toLowerCase();

  if (element.id) {
    return `${tag}#${escapeCssIdentifier(element.id)}`;
  }

  const classes = Array.from(element.classList)
    .slice(0, 2)
    .map((name) => `.${escapeCssIdentifier(name)}`)
    .join('');

  const parent = element.parentElement;

  if (!parent) {
    return `${tag}${classes}`;
  }

  const siblings = Array.from(parent.children).filter(
    (child) => child.tagName.toLowerCase() === tag,
  );

  if (siblings.length <= 1) {
    return `${tag}${classes}`;
  }

  const index = siblings.indexOf(element) + 1;

  return `${tag}${classes}:nth-of-type(${index})`;
}

function buildElementPath(element: HTMLElement): string {
  const segments: string[] = [];
  let current: HTMLElement | null = element;

  while (current) {
    segments.unshift(formatElementSegment(current));

    if (current.tagName.toLowerCase() === 'html') {
      break;
    }

    current = current.parentElement;
  }

  return segments.join('>');
}

function resolveWidgetSkipRoot(widget: HTMLElement | null): HTMLElement | null {
  if (!widget) {
    return null;
  }

  const rootNode = widget.getRootNode();

  if (rootNode instanceof ShadowRoot && rootNode.host instanceof HTMLElement) {
    return rootNode.host;
  }

  return widget;
}

function getTargetCandidate(target: EventTarget | null, skipRoot: HTMLElement | null): HTMLElement | null {
  let current = target instanceof HTMLElement ? target : null;

  while (current) {
    if (skipRoot && (current === skipRoot || skipRoot.contains(current))) {
      return null;
    }

    if (isVisibleBlockElement(current)) {
      return current;
    }

    current = current.parentElement;
  }

  return null;
}

function createTargetSelection(element: HTMLElement): TargetSelection {
  return {
    element,
    path: buildElementPath(element),
    text: normalizeElementText(element),
    rect: getElementRect(element),
  };
}

function formatTargetedPrompt(
  selection: TargetSelection,
  action: string,
  pageContext: PageContext,
): string {
  const url = pageContext.currentUrl?.trim() || window.location.href;
  const template = pageContext.template?.trim() || '[unknown template]';
  const requestedChange = action.trim();

  return [
    `The user is requesting the following updates to the page located at \`${url}\`. This page loads the template: \`${template}\`.`,
    '',
    `Affected content: \`${selection.path}\``,
    '',
    'Requested change:',
    requestedChange,
  ].join('\n');
}

function getTargetPopoverPosition(rect: ElementRect): TargetPopoverPosition {
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  const width = Math.min(360, Math.max(280, viewportWidth - 24));
  const left = clamp(rect.left, 12, Math.max(12, viewportWidth - width - 12));
  const preferredTop = rect.top + rect.height + 12;
  const maxTop = Math.max(12, viewportHeight - 200);
  const top = preferredTop + 188 <= viewportHeight
    ? preferredTop
    : clamp(rect.top - 188 - 12, 12, maxTop);

  return { top, left, width };
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
  const [targetPrompt, setTargetPrompt] = useState('');
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
  const widgetRef = useRef<HTMLElement | null>(null);
  const targetComposerRef = useRef<HTMLTextAreaElement | null>(null);
  const currentTurnId = useRef<string | null>(null);
  const currentThinkingId = useRef<string | null>(null);
  const currentAssistantId = useRef<string | null>(null);
  const promptId = useRef(uid());
  const targetPromptId = useRef(uid());
  const resizeSessionRef = useRef<ResizeSession | null>(null);
  const lastPointerPositionRef = useRef<{ x: number; y: number } | null>(null);
  const [isTargeting, setIsTargeting] = useState(false);
  const [hoveredTarget, setHoveredTarget] = useState<TargetSelection | null>(null);
  const [selectedTarget, setSelectedTarget] = useState<TargetSelection | null>(null);
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

  useEffect(() => {
    if (selectedTarget) {
      targetComposerRef.current?.focus();
    }
  }, [selectedTarget]);

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
    if (isTargeting) {
      return selectedTarget ? 'Describe the change' : 'Pick an element';
    }

    if (!configured) {
      return 'LLM not configured';
    }

    if (!canChat) {
      return context === 'cp' ? 'Sign in required' : 'CP login required';
    }

    return context === 'cp' ? 'Control Panel' : '';
  }, [canChat, configured, context, isTargeting, selectedTarget]);

  const submitMessage = useCallback(
    (value: string) => {
      const text = value.trim();

      if (!text || isStreaming || !canChat || !configured) {
        return false;
      }

      setPrompt('');
      setIsOpen(true);
      setIsStreaming(true);

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

      return true;
    },
    [canChat, chatUrl, configured, context, history, isStreaming, pageContext],
  );

  const handleSubmit = useCallback(
    () => {
      submitMessage(prompt);
    },
    [prompt, submitMessage],
  );

  const startTargeting = useCallback(() => {
    if (isStreaming || !canChat || !configured) {
      return;
    }

    setIsOpen(false);
    setIsTargeting(true);
    setHoveredTarget(null);
    setSelectedTarget(null);
    setTargetPrompt('');
  }, [canChat, configured, isStreaming]);

  const cancelTargeting = useCallback(() => {
    setIsTargeting(false);
    setHoveredTarget(null);
    setSelectedTarget(null);
    setTargetPrompt('');
  }, []);

  const submitTargetedPrompt = useCallback(() => {
    if (!selectedTarget) {
      return;
    }

    const action = targetPrompt.trim();

    if (!action) {
      return;
    }

    const didSubmit = submitMessage(formatTargetedPrompt(selectedTarget, action, pageContext));

    if (!didSubmit) {
      return;
    }

    setIsTargeting(false);
    setHoveredTarget(null);
    setSelectedTarget(null);
    setTargetPrompt('');
  }, [pageContext, selectedTarget, submitMessage, targetPrompt]);

  useEffect(() => {
    if (!isTargeting) {
      lastPointerPositionRef.current = null;
      return;
    }

    const refreshHoveredTarget = (target: EventTarget | null) => {
      const candidate = getTargetCandidate(target, resolveWidgetSkipRoot(widgetRef.current));

      setHoveredTarget((prev) => {
        if (!candidate) {
          return prev === null ? prev : null;
        }

        if (prev?.element === candidate) {
          const rect = getElementRect(candidate);

          if (
            prev.rect.top === rect.top
            && prev.rect.left === rect.left
            && prev.rect.width === rect.width
            && prev.rect.height === rect.height
          ) {
            return prev;
          }

          return { ...prev, rect };
        }

        return createTargetSelection(candidate);
      });
    };

    const refreshFromPointer = () => {
      const pointer = lastPointerPositionRef.current;

      if (!pointer || selectedTarget) {
        return;
      }

      refreshHoveredTarget(document.elementFromPoint(pointer.x, pointer.y));
    };

    const handlePointerMove = (event: PointerEvent) => {
      if (selectedTarget) {
        return;
      }

      lastPointerPositionRef.current = { x: event.clientX, y: event.clientY };
      refreshHoveredTarget(event.target);
    };

    const handleClick = (event: MouseEvent) => {
      const candidate = getTargetCandidate(event.target, resolveWidgetSkipRoot(widgetRef.current));

      if (!candidate || selectedTarget) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      setSelectedTarget(createTargetSelection(candidate));
      setHoveredTarget(createTargetSelection(candidate));
    };

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        cancelTargeting();
      }
    };

    const refreshSelected = () => {
      if (!selectedTarget) {
        refreshFromPointer();
        return;
      }

      const element = selectedTarget.element;

      if (!element.isConnected) {
        cancelTargeting();
        return;
      }

      setSelectedTarget((prev) => (prev ? { ...prev, rect: getElementRect(element) } : prev));
      setHoveredTarget((prev) => (prev ? { ...prev, rect: getElementRect(element) } : prev));
    };

    const previousCursor = document.body.style.cursor;
    document.body.style.cursor = selectedTarget ? previousCursor : 'crosshair';

    document.addEventListener('pointermove', handlePointerMove, true);
    document.addEventListener('click', handleClick, true);
    document.addEventListener('keydown', handleKeyDown, true);
    window.addEventListener('resize', refreshSelected);
    window.addEventListener('scroll', refreshSelected, true);

    return () => {
      document.body.style.cursor = previousCursor;
      document.removeEventListener('pointermove', handlePointerMove, true);
      document.removeEventListener('click', handleClick, true);
      document.removeEventListener('keydown', handleKeyDown, true);
      window.removeEventListener('resize', refreshSelected);
      window.removeEventListener('scroll', refreshSelected, true);
    };
  }, [cancelTargeting, isTargeting, selectedTarget]);

  const targetHighlight = selectedTarget ?? hoveredTarget;
  const targetPopoverPosition = useMemo(
    () => (selectedTarget ? getTargetPopoverPosition(selectedTarget.rect) : null),
    [selectedTarget],
  );

  return (
    <section className={`skills-chat-widget${isOpen ? ' skills-chat-widget--open' : ''}`} ref={widgetRef}>
      <button
        aria-controls="skills-chat-panel"
        aria-expanded={isTargeting ? false : isOpen}
        className="skills-chat-launcher"
        onClick={() => {
          if (isTargeting) {
            cancelTargeting();
            return;
          }

          setIsOpen((open) => !open);
        }}
        type="button"
      >
        <span className="skills-chat-launcherLabel">{isTargeting ? 'Cancel' : 'AI'}</span>
        {statusLabel ? <span className="skills-chat-launcherMeta">{statusLabel}</span> : null}
      </button>

      {isOpen && !isTargeting ? (
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
                      <ReactMarkdown
                        components={{
                          a: ({ ...props }) => (
                            <a {...props} rel="noreferrer noopener" target="_blank" />
                          ),
                        }}
                        remarkPlugins={[remarkGfm]}
                      >
                        {entry.role === 'assistant'
                          ? entry.text || (entry.streaming ? ' ' : '')
                          : entry.text || (entry.streaming ? ' ' : null)}
                      </ReactMarkdown>
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
                <div className="skills-chat-composerField">
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
                  <button
                    aria-label="Target an element on the page"
                    className="skills-chat-targetButton"
                    disabled={isStreaming}
                    onClick={startTargeting}
                    title="Target an element on the page"
                    type="button"
                  >
                    <svg
                      aria-hidden="true"
                      fill="none"
                      focusable="false"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={1.75}
                      viewBox="0 0 24 24"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <circle cx="12" cy="12" r="8" />
                      <circle cx="12" cy="12" r="3" />
                      <line x1="12" y1="2" x2="12" y2="5" />
                      <line x1="12" y1="19" x2="12" y2="22" />
                      <line x1="2" y1="12" x2="5" y2="12" />
                      <line x1="19" y1="12" x2="22" y2="12" />
                    </svg>
                  </button>
                </div>

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

      {isTargeting ? (
        <div aria-hidden="true" className="skills-target-layer">
          {targetHighlight ? (
            <div
              className="skills-target-highlight"
              style={{
                top: `${targetHighlight.rect.top}px`,
                left: `${targetHighlight.rect.left}px`,
                width: `${targetHighlight.rect.width}px`,
                height: `${targetHighlight.rect.height}px`,
              }}
            />
          ) : null}

          {selectedTarget && targetPopoverPosition ? (
            <div
              className="skills-target-composer"
              role="dialog"
              style={{
                top: `${targetPopoverPosition.top}px`,
                left: `${targetPopoverPosition.left}px`,
                width: `${targetPopoverPosition.width}px`,
              }}
            >
              <div className="skills-target-composerLabel">Targeted edit</div>
              <div className="skills-target-composerMeta">{selectedTarget.path}</div>
              <div className="skills-target-composerText">{selectedTarget.text}</div>
              <label className="visually-hidden" htmlFor={targetPromptId.current}>
                Describe the change
              </label>
              <textarea
                id={targetPromptId.current}
                onChange={(event) => setTargetPrompt(event.target.value)}
                onKeyDown={(event) => {
                  if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    submitTargetedPrompt();
                  }
                }}
                placeholder="Describe the change to this element..."
                ref={targetComposerRef}
                rows={3}
                value={targetPrompt}
              />
              <div className="skills-target-composerFooter">
                <button className="skills-chat-secondaryButton" onClick={cancelTargeting} type="button">
                  Cancel
                </button>
                <button
                  disabled={isStreaming || targetPrompt.trim().length === 0}
                  onClick={submitTargetedPrompt}
                  type="button"
                >
                  Send
                </button>
              </div>
            </div>
          ) : null}
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
