import { startTransition, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

type ToolStatus = 'running' | 'complete';

type ToolEvent = {
  id: string;
  kind: 'tool';
  label: string;
  detail: string;
  status: ToolStatus;
};

type MessageEvent = {
  id: string;
  kind: 'message';
  role: 'user' | 'assistant';
  text: string;
  streaming?: boolean;
};

type TimelineEvent = ToolEvent | MessageEvent;

const initialTimeline: TimelineEvent[] = [
  {
    id: 'welcome',
    kind: 'message',
    role: 'assistant',
    text: 'Craft Skill chat UI is ready. Backend transport is still mocked, but this page already supports message and tool streaming states.',
  },
  {
    id: 'tool-schema',
    kind: 'tool',
    label: 'sections/list',
    detail: 'Example tool output streams here before the final assistant response.',
    status: 'complete',
  },
];

function App({ mcpPath }: { mcpPath: string }) {
  const [timeline, setTimeline] = useState<TimelineEvent[]>(initialTimeline);
  const [prompt, setPrompt] = useState('');
  const [isStreaming, setIsStreaming] = useState(false);
  const conversationRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    const container = conversationRef.current;

    if (!container) {
      return;
    }

    container.scrollTop = container.scrollHeight;
  }, [timeline]);

  const chatSummary = useMemo(() => {
    const count = timeline.filter((event) => event.kind === 'message').length;

    return `${count} message${count === 1 ? '' : 's'}`;
  }, [timeline]);

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const nextPrompt = prompt.trim();

    if (!nextPrompt || isStreaming) {
      return;
    }

    const userMessageId = crypto.randomUUID();
    const toolId = crypto.randomUUID();
    const assistantMessageId = crypto.randomUUID();
    const finalResponse = `Mocked response for: "${nextPrompt}". This placeholder simulates the eventual Craft-powered assistant, including a streamed reply after tool activity in the timeline.`;

    setPrompt('');
    setIsStreaming(true);
    setTimeline((current) => [
      ...current,
      {
        id: userMessageId,
        kind: 'message',
        role: 'user',
        text: nextPrompt,
      },
      {
        id: toolId,
        kind: 'tool',
        label: 'entries/search',
        detail: 'Preparing mocked tool stream...',
        status: 'running',
      },
      {
        id: assistantMessageId,
        kind: 'message',
        role: 'assistant',
        text: '',
        streaming: true,
      },
    ]);

    window.setTimeout(() => {
      startTransition(() => {
        setTimeline((current) =>
          current.map((entry) =>
            entry.id === toolId
              ? {
                  ...entry,
                  detail: `Querying Craft content via /${mcpPath} (mocked).`,
                }
              : entry,
          ),
        );
      });
    }, 350);

    window.setTimeout(() => {
      startTransition(() => {
        setTimeline((current) =>
          current.map((entry) =>
            entry.id === toolId
              ? {
                  ...entry,
                  detail: 'Tool completed. Waiting for streamed assistant narration...',
                  status: 'complete',
                }
              : entry,
          ),
        );
      });
    }, 900);

    let index = 0;
    const interval = window.setInterval(() => {
      index += 1;

      startTransition(() => {
        setTimeline((current) =>
          current.map((entry) =>
            entry.id === assistantMessageId
              ? {
                  ...entry,
                  text: finalResponse.slice(0, index),
                  streaming: index < finalResponse.length,
                }
              : entry,
          ),
        );
      });

      if (index >= finalResponse.length) {
        window.clearInterval(interval);
        setIsStreaming(false);
      }
    }, 16);
  };

  return (
    <section className="skills-chat-shell">
      <header className="skills-chat-header">
        <div>
          <p className="skills-chat-eyebrow">Craft Control Panel</p>
          <h2>AI Chat</h2>
          <p className="skills-chat-subtitle">
            Standard chat layout with mocked tool and assistant streaming.
          </p>
        </div>

        <div className="skills-chat-meta">
          <span>{chatSummary}</span>
          <span>MCP path: /{mcpPath}</span>
        </div>
      </header>

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
                <p>{entry.detail}</p>
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

        <form className="skills-chat-composer" onSubmit={handleSubmit}>
          <label className="visually-hidden" htmlFor="skills-chat-prompt">
            Prompt
          </label>
          <textarea
            id="skills-chat-prompt"
            onChange={(event) => setPrompt(event.target.value)}
            placeholder="Ask Craft Skill to create content, inspect sections, or draft a change..."
            rows={3}
            value={prompt}
          />

          <div className="skills-chat-composerFooter">
            <span>Frontend scaffold only. No backend calls yet.</span>
            <button disabled={isStreaming || prompt.trim().length === 0} type="submit">
              {isStreaming ? 'Streaming...' : 'Send'}
            </button>
          </div>
        </form>
      </div>
    </section>
  );
}

const rootElement = document.querySelector<HTMLElement>('[data-skills-chat-root]');

if (rootElement) {
  const mcpPath = rootElement.dataset.mcpPath ?? 'mcp';
  createRoot(rootElement).render(<App mcpPath={mcpPath} />);
}
