---
title: ADR Template
description: Template for creating new Architecture Decision Records
created: 2024-01-28
updated: 2024-01-28
version: 1.0.0
category: adr
status: template
---

# ADR-XXX: Title of Decision

> Brief one-sentence summary of the decision.

---

## Status

**[PROPOSED | ACCEPTED | DEPRECATED | SUPERSEDED]** - [Brief context about status]

---

## Context

### Background

Explain the issue or problem that prompted this decision. What situation are we trying to address?

### Problem Statement

What is the specific problem we're trying to solve? Why does it matter?

### Current State

What is the current approach or system state?

### Constraints

What are the limitations or constraints we must work within?

- Technical constraints
- Business requirements
- Team capabilities
- Performance requirements
- Scalability needs

### Stakeholders

Who are the stakeholders affected by this decision?

- Team members
- Users
- Related systems
- Future maintainers

---

## Decision

### Proposed Solution

What is the decision we're making?

### Rationale

Why is this the best solution? What makes it superior to alternatives?

### Key Principles

What principles guide this decision?

### Implementation Approach

How will this decision be implemented? At a high level, what steps are involved?

### Design Diagram (if applicable)

```mermaid
graph LR
    A[Component A] --> B[Component B]
    B --> C[Component C]
```

---

## Consequences

### Positive Effects

What benefits does this decision bring?

- Benefit 1: Explanation
- Benefit 2: Explanation
- Benefit 3: Explanation

### Negative Effects

What are the trade-offs or downsides?

- Drawback 1: Explanation
- Drawback 2: Explanation
- Drawback 3: Explanation

### Risks

What risks should we be aware of?

- Risk 1: Impact and mitigation strategy
- Risk 2: Impact and mitigation strategy

### Learning Opportunities

What do we expect to learn from this decision?

---

## Alternatives Considered

### Alternative 1: [Name]

**Description:** Brief description of this approach

**Pros:**
- Advantage 1
- Advantage 2

**Cons:**
- Disadvantage 1
- Disadvantage 2

**Why rejected:** Explanation of why this was not chosen

---

### Alternative 2: [Name]

**Description:** Brief description of this approach

**Pros:**
- Advantage 1
- Advantage 2

**Cons:**
- Disadvantage 1
- Disadvantage 2

**Why rejected:** Explanation of why this was not chosen

---

## Implementation Plan

### Phase 1: Planning

- [ ] Task 1
- [ ] Task 2
- [ ] Task 3

**Timeline:** [Expected duration]

### Phase 2: Development

- [ ] Task 1
- [ ] Task 2
- [ ] Task 3

**Timeline:** [Expected duration]

### Phase 3: Testing and Validation

- [ ] Task 1
- [ ] Task 2
- [ ] Task 3

**Timeline:** [Expected duration]

### Phase 4: Deployment

- [ ] Task 1
- [ ] Task 2
- [ ] Task 3

**Timeline:** [Expected duration]

---

## Success Criteria

How will we know if this decision was successful?

- [ ] Criterion 1: Measurable outcome
- [ ] Criterion 2: Measurable outcome
- [ ] Criterion 3: Measurable outcome

---

## Related Decisions

Link to related ADRs:

- [[ADR-001-Modular-Architecture|ADR-001: Modular Architecture]] (example)
- [[ADR-002-Database-Abstraction|ADR-002: Database Abstraction]] (example)

---

## References and Resources

### Documentation

- [Link to related documentation]
- [Link to specification]

### Research

- [Link to research paper or article]
- [Link to case study]

### Tools and Libraries

- [Link to relevant tool]
- [Link to relevant library]

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | YYYY-MM-DD | Your Name | Initial version |

---

## Glossary

**Term 1:** Definition of important term

**Term 2:** Definition of important term

**Term 3:** Definition of important term

---

## Questions and Discussion

### Frequently Asked Questions

**Q: Why was approach X not chosen?**

A: Explanation of decision rationale

**Q: How does this affect [component]?**

A: Explanation of impact

---

## Approval and Sign-Off

- [ ] Technical Lead: [Name] - Date: [Date]
- [ ] Architect: [Name] - Date: [Date]
- [ ] Project Lead: [Name] - Date: [Date]

---

## Template Instructions

When using this template to create a new ADR:

1. **Replace ADR-XXX** with the next sequential number (e.g., ADR-006)
2. **Fill in the Status** as PROPOSED initially
3. **Complete the Context** section with background information
4. **Detail the Decision** with clear rationale
5. **Analyze Consequences** - both positive and negative
6. **Document Alternatives** considered
7. **Create an Implementation Plan** with realistic timelines
8. **Define Success Criteria** that can be measured
9. **Include a Revision History** tracking changes
10. **Get Approvals** before finalizing as ACCEPTED

### Tips for Good ADRs

- **Be concise** but complete
- **Use diagrams** for complex decisions
- **Document trade-offs** honestly
- **Think long-term** about consequences
- **Update status** as decision evolves
- **Link to related** ADRs
- **Include examples** when helpful
- **Review with team** before finalizing

---

## Related Documentation

- [[ADR-Index|ADR Index - List of all ADRs]]
- [[ADR-001-Modular-Architecture|ADR-001: Modular Architecture]]
- [[../Contributing|Contributing Overview]]

---

#xoops #adr #architecture #decision-record #template
