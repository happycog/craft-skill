> [!IMPORTANT]
> Call this prompt with: Act on the instructions in @prompts/implement-spec.md based on the spec @specs/[spec-file].md

Make sure you're aware of `@AGENTS.md` and then read the spec file specified by the user.

## Spec Implementation Instructions

This prompt is designed to guide the implementation of specification files in this repository. It emphasizes incremental development with continuous testing and documentation updates.

### Implementation Process

1. First, familiarize yourself with the current state of the project and overall project structure in `@AGENTS.md`
2. Read the specific specification file in full to understand all requirements
3. Explore the existing codebase to understand the current implementation state
4. Create a detailed implementation plan with small, incremental steps
5. Follow the implementation checklist below, updating it as you progress

### Implementation Checklist

**Preparation Phase:**
- [ ] Read and understand the specification completely
- [ ] Analyze the existing codebase and project structure
- [ ] Identify all components/files that need to be created or modified
- [ ] Create a step-by-step implementation plan
- [ ] Set up any necessary development environment

**Incremental Implementation Phase:**
- [ ] Implement the first small component/feature
- [ ] Write tests for the component immediately (don't wait until the end)
- [ ] Update the spec file with implementation notes and progress
- [ ] Verify the component works as expected
- [ ] Commit the incremental change
- [ ] Repeat for each subsequent component/feature

**Integration Phase:**
- [ ] Integrate all components together
- [ ] Run comprehensive tests across the entire implementation
- [ ] Update documentation and spec file with final implementation details
- [ ] Verify all original requirements are met
- [ ] Perform final testing and validation

### Key Implementation Guidelines

1. **Incremental Development**: Implement in small, testable chunks rather than building everything at once
2. **Test-Driven Approach**: Write tests for each component as you build it, not at the end
3. **Continuous Documentation**: Update the spec file as you implement to track progress and note any deviations
4. **Frequent Verification**: Test each component independently before moving to the next
5. **Regular Commits**: Commit working increments frequently to maintain a clear development history

### Implementation Output

As you implement the specification:
- Create clear, well-documented code that follows project conventions
- Write comprehensive tests that validate functionality
- Update the specification file with:
  - Implementation progress notes
  - Any changes or deviations from the original plan
  - Links to relevant code files
  - Test results and validation notes
- Maintain a clear commit history showing incremental progress

### Handling Blockers

If you encounter issues during implementation:
1. Document the blocker in the spec file
2. Research alternative approaches
3. Seek clarification on requirements if needed
4. Implement workarounds where appropriate
5. Update the implementation plan as needed

Remember: The goal is steady, incremental progress with continuous validation rather than attempting to implement everything at once.