> [!IMPORTANT]
> Call this prompt with: Act on the instructions in @prompts/review-spec.md based on the spec @specs/0001-do-something.md

Make sure you're aware of `@AGENTS.md` and then read the spec file specified by the user.

## Spec Review Instructions

This prompt is designed to guide the review of specification files in this repository.

### Review Process

1. First, familiarize yourself with the current state of the project and overall project structure in `@AGENTS.md`
2. Read the specific specification file in full
3. Evaluate the specification against the following criteria:
    - Clarity and completeness of requirements
    - Feasibility of implementation
    - Alignment with project goals
    - Proper structure and formatting
4. If you need more information feel free to perform non-destructive reads on the codebase so you can better review the spec

### Review Checklist

- [ ] Requirements are clearly defined
- [ ] Expected outcomes are specific and measurable
- [ ] Implementation approach is feasible
- [ ] Non-requirements are documented as out of scope but included for future consideration
- [ ] Specification follows the established template format (defined below)
- [ ] All necessary context and background information is provided

### Review Output

Provide feedback in a structured format addressing each of the checklist items above. With approval you may update the spec file to answer questions, reorganize thoughts, or just clean up the document.

## Specification Template

Use the following template for all specifications:

# {title}

## Background
A brief overview of the specification, why it is needed, and any relevant context.

## Goal
A brief overview of what the specification aims to achieve.

## Implementation Requirements

### 1. {First Major Requirement}
- {Detailed sub-requirements}
- {More details as needed}

## Technical Implementation Notes

### {notes on relevant technical systems such as the database, routing, component, error handling}
- {specific technical details}

## Non-Requirements (Future Considerations)

## Acceptance Criteria
