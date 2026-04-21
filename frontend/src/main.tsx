import { startTransition, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

// ─── Helpers ─────────────────────────────────────────────────────────

/** uid() requires a secure context (HTTPS). Fall back for local dev over HTTP. */
const uid = (): string =>
  typeof crypto.randomUUID === 'function'
    ? crypto.randomUUID()
    : `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;

// ─── Types ───────────────────────────────────────────────────────────

type ToolStatus = 'running' | 'complete' | 'error';

type ToolEvent = {
  id: string;
  kind: 'tool';
  label: string;
  detail: string;
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
  callbacks: SseCallback,
) {
  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ messages, message: userMessage }),
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
    text: 'Hi! I\u2019m your Craft CMS assistant. Ask me to create entries, search content, manage fields, or anything else \u2014 I have full access to your Craft installation.',
  },
];

function App({ chatUrl }: { chatUrl: string }) {
  const [timeline, setTimeline] = useState<TimelineEvent[]>(welcomeTimeline);
  const [history, setHistory] = useState<InternalMessage[]>([]);
  const [prompt, setPrompt] = useState('');
  const [isStreaming, setIsStreaming] = useState(false);
  const conversationRef = useRef<HTMLDivElement | null>(null);
  const currentTurnId = useRef<string | null>(null);
  const currentThinkingId = useRef<string | null>(null);
  const currentAssistantId = useRef<string | null>(null);

  // Auto-scroll to bottom on new content
  useEffect(() => {
    const el = conversationRef.current;

    if (el) {
      el.scrollTop = el.scrollHeight;
    }
  }, [timeline]);

  const chatSummary = useMemo(() => {
    const count = timeline.filter((e) => e.kind === 'message').length;

    return `${count} message${count === 1 ? '' : 's'}`;
  }, [timeline]);

  const handleSubmit = useCallback(
    () => {
      const text = prompt.trim();

      if (!text || isStreaming) {
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

      streamChat(chatUrl, history, text, {
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
                label: name,
                detail: Object.keys(input).length
                  ? JSON.stringify(input, null, 2)
                  : 'Executing\u2026',
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
                      detail: typeof result === 'object'
                        ? JSON.stringify(result, null, 2).slice(0, 500)
                        : String(result).slice(0, 500),
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
                  text: `\u274c Error: ${message}`,
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
              text: `\u274c Network error: ${err instanceof Error ? err.message : String(err)}`,
            },
          ]);
        });

        setIsStreaming(false);
      });
    },
    [prompt, isStreaming, history, chatUrl],
  );

  return (
    <section className="skills-chat-shell">
      <div className="skills-chat-frame">
        <div className="skills-chat-conversation" ref={conversationRef}>
          {timeline.map((entry) =>
            entry.kind === 'tool' ? (
              <article className="skills-tool-card" key={entry.id}>
                <div className="skills-tool-row">
                  <strong>{entry.label}</strong>
                  <span className={`skills-tool-status skills-tool-status--${entry.status}`}>
                    {entry.status}
                  </span>
                </div>
                <pre className="skills-tool-detail">{entry.detail}</pre>
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
                  {entry.text || (entry.streaming ? ' ' : null)}
                  {entry.streaming ? <span className="skills-cursor" /> : null}
                </div>
              </article>
            ),
          )}
        </div>

        <div className="skills-chat-composer">
          <label className="visually-hidden" htmlFor="skills-chat-prompt">
            Prompt
          </label>
          <textarea
            id="skills-chat-prompt"
            onChange={(e) => setPrompt(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSubmit();
              }
            }}
            placeholder="Ask me to create content, search entries, manage fields\u2026"
            rows={3}
            value={prompt}
          />

          <div className="skills-chat-composerFooter">
            <span>{chatSummary}</span>
            <button
              disabled={isStreaming || prompt.trim().length === 0}
              onClick={handleSubmit}
              type="button"
            >
              {isStreaming ? 'Thinking\u2026' : 'Send'}
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}

// ─── Mount ───────────────────────────────────────────────────────────

const rootElement = document.querySelector<HTMLElement>('[data-skills-chat-root]');

if (rootElement) {
  const chatUrl = rootElement.dataset.chatUrl ?? '';

  createRoot(rootElement).render(<App chatUrl={chatUrl} />);
}
