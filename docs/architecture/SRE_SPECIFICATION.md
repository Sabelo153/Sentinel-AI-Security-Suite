# Sentinel Rule Engine (SRE) Specification

**Version**: 1.0  
**Status**: Ready for Implementation  
**Type**: Core Component  
**Priority**: Phase 1

---

## 🎯 Overview

The **Sentinel Rule Engine (SRE)** is a rules-based automation system that intelligently evaluates business logic and triggers appropriate actions without requiring external AI services for every decision.

Think of it as: **"Smart workflow automation powered by business rules"**

---

## 💡 Why SRE?

### The Problem
```
Guard clocks in → System waits for AI → API call to external service → Delay → User frustration
```

### The Solution
```
Guard clocks in → SRE evaluates rules → Action triggered instantly → AI used only if needed
```

### Benefits

| Benefit | Impact |
|---------|--------|
| **Speed** | Business rules evaluated in milliseconds (vs seconds for AI) |
| **Cost** | Reduces API calls to external services ($$$ saved) |
| **Consistency** | Company policies enforced exactly the same way every time |
| **Auditability** | Every rule evaluation is logged and traceable |
| **Control** | Managers can define rules without writing code |
| **Reliability** | No external dependencies for core operations |

---

## 🏗️ SRE Architecture

### Components

```
┌─────────────────────────────────────────┐
│         Event Occurs                    │
│    (GuardClockInEvent)                  │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│      Rule Engine (SRE)                  │
├─────────────────────────────────────────┤
│  1. Load applicable rules               │
│  2. Gather context data                 │
│  3. Evaluate each rule                  │
│  4. Determine actions                   │
│  5. Execute actions                     │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│      Rule Evaluation Result             │
│                                         │
│  {                                      │
│    "event": "GuardClockInEvent",       │
│    "rules_evaluated": 5,                │
│    "rules_matched": 2,                  │
│    "severity": "High",                  │
│    "actions": [                         │
│      "CreateOccurrenceEntry",          │
│      "NotifySupervisor",               │
│      "FlagForAI"                       │
│    ]                                    │
│  }                                      │
└──────────────┬──────────────────────────┘
               ↓
       Execute Actions
```

---

## 📋 Rule Definition

### Rule Structure

```
{
  "rule_id": "late_arrival_high_severity",
  "domain": "attendance",
  "event_type": "GuardClockInEvent",
  "enabled": true,
  "priority": 10,
  
  "conditions": [
    {
      "field": "clock_in_delay_minutes",
      "operator": ">=",
      "value": 30,
      "context": "shift"
    },
    {
      "field": "late_arrivals_this_week",
      "operator": ">=",
      "value": 3,
      "context": "historical"
    }
  ],
  
  "condition_logic": "AND",  // All conditions must match
  
  "severity": "High",
  
  "actions": [
    {
      "action_type": "CreateOccurrenceEntry",
      "params": {
        "entry_type": "Attendance",
        "description": "Late arrival - pattern detected",
        "auto_notify": true
      }
    },
    {
      "action_type": "SendNotification",
      "params": {
        "recipient_role": "Supervisor",
        "channel": ["Email", "SMS"],
        "priority": "High",
        "template": "late_arrival_pattern"
      }
    },
    {
      "action_type": "FlagForAI",
      "params": {
        "ai_task": "GenerateExplanation",
        "template": "attendance_explanation"
      }
    }
  ],
  
  "audit": {
    "created_by": "system",
    "created_at": "2025-01-01T00:00:00Z",
    "last_modified": "2025-01-01T00:00:00Z"
  }
}
```

---

## 🔍 Rule Types

### 1. Time-Based Rules
Trigger based on time thresholds.

```
Rule: If clock_in_delay > 30 minutes → Notify supervisor

Context:
  - Guard should clock in at 06:00
  - Guard clocked in at 06:45
  - Delay = 45 minutes
  - Rule triggers: YES

Action: Send notification to supervisor
```

### 2. Frequency Rules
Trigger based on how often something happens.

```
Rule: If late_arrivals_this_week >= 3 → Severity = HIGH

Context:
  - Monday: Late by 10 mins
  - Wednesday: Late by 15 mins
  - Thursday: Late by 20 mins (current event)
  - Frequency = 3 times
  - Rule triggers: YES

Action: Escalate to manager, flag for discussion
```

### 3. Threshold Rules
Trigger when a value crosses a boundary.

```
Rule: If site_visitors > 100 → Alert security

Context:
  - Current visitors on site = 105
  - Threshold = 100
  - Rule triggers: YES

Action: Send alert, increase patrols
```

### 4. Status-Based Rules
Trigger based on current status.

```
Rule: If vehicle_inspection_status = Failed → Block site entry

Context:
  - Vehicle inspection yesterday: FAILED
  - Vehicle attempting entry today
  - Status = Failed
  - Rule triggers: YES

Action: Deny entry, notify driver
```

### 5. Compound Rules
Trigger when multiple conditions are true.

```
Rule: If (late_arrival AND high_risk_shift AND manager_on_duty) → Escalate

Context:
  - Guard is 30 mins late: YES
  - Working on high-risk shift: YES
  - Manager on duty: YES
  - All conditions met: YES
  - Rule triggers: YES

Action: Page manager immediately
```

### 6. Sequential Rules
Trigger based on sequence of events.

```
Rule: If (patrol_missed AND 2nd_time_this_month AND same_guard) → Investigate

Context:
  - Patrol missed today: YES
  - Guard missed patrol on 12th: YES
  - Same guard: YES
  - Sequence detected: YES
  - Rule triggers: YES

Action: Create investigation task, meet with guard
```

---

## 🎯 Rule Examples by Domain

### Attendance Domain

**Rule 1: Late Arrival - Medium Severity**
```
Trigger: Guard clocks in 15-30 minutes late
Action:
  - Log in occurrence book
  - Deduct from attendance percentage
  - Alert supervisor (optional based on policy)
Severity: Medium
```

**Rule 2: Late Arrival - High Severity**
```
Trigger: Guard clocks in 30+ minutes late OR pattern detected
Action:
  - Log in occurrence book as high-priority
  - Alert supervisor immediately
  - Alert HR for pattern analysis
  - Create follow-up task
Severity: High
```

**Rule 3: Excessive Overtime**
```
Trigger: Guard working 4+ hours overtime in one day OR 20+ hours in week
Action:
  - Flag for supervisor review
  - Alert payroll system (overtime pay adjustment)
  - Check for compliance with labor regulations
Severity: Medium
```

### Patrol Domain

**Rule 4: Missed Checkpoint**
```
Trigger: Patrol guard doesn't scan checkpoint within 15 min of scheduled time
Action:
  - Alert patrol supervisor
  - Create occurrence book entry
  - If repeated: escalate to manager
Severity: High
```

**Rule 5: Patrol Deviation**
```
Trigger: Guard's GPS location > 50 meters from patrol route
Action:
  - Alert dispatch
  - Create occurrence entry
  - Request explanation
Severity: High
```

### Visitor Domain

**Rule 6: Visitor Checkout Overdue**
```
Trigger: Visitor badge not scanned out by checkout time
Action:
  - Alert security
  - Attempt to contact visitor
  - Create occurrence entry
  - Review security footage
Severity: High
```

**Rule 7: Visitor in Restricted Area**
```
Trigger: Visitor badge scanned in area not on their visit approval
Action:
  - Deny access (block door)
  - Alert security immediately
  - Create high-priority incident
Severity: Critical
```

### Fleet Domain

**Rule 8: Vehicle Inspection Overdue**
```
Trigger: Vehicle not inspected for 30 days
Action:
  - Block vehicle from operational use
  - Alert fleet manager
  - Schedule inspection
  - Create maintenance task
Severity: High
```

**Rule 9: Vehicle Fuel Low**
```
Trigger: Vehicle fuel < 25%
Action:
  - Alert driver
  - Suggest refueling location
  - Log for fuel consumption analysis
Severity: Medium
```

### Incident Domain

**Rule 10: High-Risk Incident Auto-Escalation**
```
Trigger: Incident created with severity = Critical
Action:
  - Alert security manager immediately
  - Alert company director
  - Create emergency response task
  - Start incident timer
  - Log in occurrence book
Severity: Critical
```

---

## 🔄 Rule Evaluation Process

### Step-by-Step

```
1. EVENT TRIGGERED
   └─ Example: GuardClockInEvent
      { employee_id: 123, site_id: 5, delay_minutes: 45, timestamp: "..." }

2. LOAD CONTEXT
   ├─ Employee data (role, shift, manager, etc.)
   ├─ Historical data (past 7/30/90 days)
   ├─ Company policies
   ├─ Site settings
   └─ Time of day, day of week, etc.

3. IDENTIFY APPLICABLE RULES
   ├─ Filter by: event_type = "GuardClockInEvent"
   ├─ Filter by: domain = "attendance"
   ├─ Filter by: enabled = true
   └─ Result: [rule_1, rule_2, rule_3, rule_5] (4 rules)

4. EVALUATE EACH RULE
   
   Rule 1: Late Arrival - Medium Severity
   ├─ Condition 1: delay_minutes >= 15? YES ✓
   ├─ Condition 2: delay_minutes < 30? YES ✓
   ├─ All conditions met: YES ✓
   └─ Result: MATCH
   
   Rule 2: Late Arrival - High Severity
   ├─ Condition 1: delay_minutes >= 30? YES ✓
   ├─ Condition 2: late_arrivals_this_week >= 3? YES ✓
   ├─ All conditions met: YES ✓
   └─ Result: MATCH
   
   Rule 3: Excessive Overtime
   ├─ Condition 1: overtime_today >= 4 hours? NO ✗
   └─ Result: NO MATCH
   
   Rule 5: Pattern Analysis
   ├─ Condition 1: late_arrivals_this_month >= 5? YES ✓
   ├─ Condition 2: has_warning_given? YES ✓
   ├─ All conditions met: YES ✓
   └─ Result: MATCH

5. AGGREGATE ACTIONS
   From matched rules, collect all actions:
   ├─ Rule 1 → Log to occurrence book
   ├─ Rule 1 → Send medium-priority notification
   ├─ Rule 2 → Send high-priority notification to supervisor
   ├─ Rule 2 → Create HR follow-up task
   └─ Rule 5 → Flag for manager review

6. DETERMINE FINAL SEVERITY
   ├─ Rule 1: Medium
   ├─ Rule 2: High
   ├─ Rule 5: High
   └─ Final: HIGH (highest of all matched)

7. EXECUTE ACTIONS
   ├─ OccurrenceBookService.createEntry(...)
   ├─ NotificationService.send(priority=High, ...)
   ├─ TaskService.createTask(type=ManagerReview, ...)
   └─ AuditService.log(RuleEvaluationEvent)

8. EMIT RESULT EVENT
   └─ RuleEvaluationCompleteEvent
      {
        event_id: "...",
        rules_evaluated: 4,
        rules_matched: 3,
        severity: "High",
        actions_executed: 3,
        timestamp: "..."
      }

9. TRIGGER ACTION HANDLERS
   ├─ NotificationService processes notification
   ├─ OccurrenceBookService updates occurrence book
   ├─ TaskService creates manager task
   ├─ DashboardService updates real-time view
   └─ AIService receives context for enhancement
```

---

## 📊 Rule Management

### Rule Storage
```
Table: rules
├─ rule_id (UUID)
├─ rule_name (string)
├─ event_type (string)
├─ domain (string)
├─ enabled (boolean)
├─ priority (integer)
├─ conditions (JSON)
├─ actions (JSON)
├─ severity (ENUM)
├─ version (integer)
├─ created_at (timestamp)
├─ updated_at (timestamp)
└─ created_by (user_id)

Table: rule_evaluations (Audit Trail)
├─ evaluation_id (UUID)
├─ rule_id (UUID)
├─ event_id (UUID)
├─ matched (boolean)
├─ context_data (JSON)
├─ execution_time_ms (integer)
├─ actions_executed (integer)
├─ evaluated_at (timestamp)
└─ evaluation_result (JSON)
```

### Rule Versioning
```
When a rule is modified:

1. Create new version (immutable)
2. Keep old version in archive
3. Apply new version to new events
4. Maintain evaluation history

Example:
  rule_1 version 1 (active) → evaluates all current events
  rule_1 version 2 (archived) → historical reference
  
Can revert to previous version if needed.
```

### Rule Monitoring
```
Track for each rule:
- Times evaluated
- Times matched
- Average execution time
- Errors/exceptions
- Last 7 days performance

Alert if:
- Rule causes error > N times
- Rule execution time > 500ms (performance)
- Rule never matches (may be misconfigured)
```

---

## 🚨 Error Handling

### When Rule Evaluation Fails

```
Scenario: Rule evaluation throws exception

1. Exception caught by SRE
2. Log error with full context
3. Create incident if severity = Critical
4. Fallback to default action
5. Alert SRE administrator
6. Continue processing other rules

Example:
  Rule evaluation: Historical data lookup failed
  ├─ Logged: ERROR - HistoricalDataException
  ├─ Fallback: Evaluate rule without historical context
  ├─ Result: Rule may not match, but system continues
  └─ Alert: SRE Admin - Fix database connection
```

### Recovery

```
If database unavailable:
- Use cached rules (last 1 hour)
- Process event anyway
- Queue for retry when online

If action fails:
- Log failure
- Queue for retry
- Continue to next action
- Don't block event processing
```

---

## ⚡ Performance Characteristics

### Target Performance

| Operation | Target | Actual |
|-----------|--------|--------|
| Rule evaluation | < 100ms | (To be benchmarked) |
| Single rule match | < 20ms | (To be benchmarked) |
| Context loading | < 50ms | (To be benchmarked) |
| Action execution | < 200ms | (To be benchmarked) |
| **Total E2E** | **< 500ms** | (To be benchmarked) |

### Optimization Strategies

1. **Rule Caching**: Load rules once per domain
2. **Lazy Loading**: Only load needed context
3. **Parallel Evaluation**: Evaluate rules in parallel when possible
4. **Database Indexing**: Fast lookups for context
5. **Query Optimization**: Minimize database calls

---

## 🧪 Testing Strategy

### Unit Tests
```
- Test each rule condition
- Test rule matching logic
- Test action execution
- Test error cases
```

### Integration Tests
```
- Test full event → rule evaluation → action flow
- Test with real database
- Test concurrent events
- Test rule versioning
```

### Scenario Tests
```
Guard clocks in late (45 mins)
├─ Verify: Occurrence book entry created ✓
├─ Verify: Supervisor notification sent ✓
├─ Verify: HR flagged for review ✓
└─ Verify: Audit log complete ✓

Patrol missed checkpoint
├─ Verify: Alert sent to supervisor ✓
├─ Verify: Occurrence created ✓
├─ Verify: If repeated: escalated ✓
└─ Verify: Audit log complete ✓
```

---

## 🔒 Security

### Rule Security

```
Rules can be:
✓ Created by: Administrators only
✓ Modified by: Administrators only
✓ Disabled by: Administrators only
✓ Viewed by: Authorized users (audit trail)

Audit Trail:
✓ Every rule change tracked
✓ Who changed what, when
✓ Before/after values
✓ Change approval workflow
```

### Rule Constraints

```
Rules cannot:
✗ Bypass role-based access control
✗ Delete records (only soft delete)
✗ Modify another company's data
✗ Create unauthorized notifications
✗ Execute external code
```

---

## 📈 Scalability

### Current Design Supports

```
Rules:     1,000+ rules per domain
Events:    10,000+ evaluations per second
Companies: Multi-tenant with isolation
Storage:   Rule evaluations archived after 90 days
```

### Future Optimization

```
1. Rule compilation (convert rules to optimized bytecode)
2. Distributed evaluation (across microservices)
3. Machine learning (learn which rules to evaluate first)
4. Rule caching (in-memory rule engine)
5. Event batching (batch similar events)
```

---

## 📚 Rule Library (Ready-Made)

Pre-built rule templates for common scenarios:

```
Attendance:
  ✓ Late Arrival Detection
  ✓ Excessive Overtime
  ✓ Unauthorized Absence
  ✓ Shift Swap Request
  
Patrols:
  ✓ Missed Checkpoint
  ✓ Route Deviation
  ✓ Excessive Stop Duration
  
Visitors:
  ✓ Checkout Overdue
  ✓ Restricted Area Access
  ✓ Badge Suspension
  
Fleet:
  ✓ Inspection Overdue
  ✓ Fuel Low
  ✓ Maintenance Due
  
Incidents:
  ✓ High-Risk Escalation
  ✓ Duplicate Detection
  ✓ Auto-Assignment
```

---

## 🎯 Implementation Plan

### Phase 1: Core Engine (Week 1-2)
- [ ] Design rule data model
- [ ] Implement rule evaluation engine
- [ ] Create rule registry
- [ ] Build unit tests

### Phase 2: Integration (Week 3-4)
- [ ] Integrate with event system
- [ ] Implement action execution
- [ ] Build error handling
- [ ] Create logging/audit

### Phase 3: Management UI (Week 5-6)
- [ ] Create rule editor
- [ ] Build rule monitoring dashboard
- [ ] Add rule versioning UI
- [ ] Create rule templates

### Phase 4: Optimization (Week 7-8)
- [ ] Performance tuning
- [ ] Caching implementation
- [ ] Load testing
- [ ] Production hardening

---

## ✅ Completion Criteria

**SRE is production-ready when**:

- [ ] All 12 rule examples tested and working
- [ ] Performance < 100ms per rule evaluation
- [ ] Error handling and recovery working
- [ ] Audit trail complete and immutable
- [ ] Rule versioning functional
- [ ] Multi-tenancy verified (no data leakage)
- [ ] Documentation complete
- [ ] Administrator training complete
- [ ] Monitoring and alerting in place
- [ ] Backup/restore tested

---

## 🚀 Next Step

The Sentinel Rule Engine is the foundation for intelligent automation. Once built, every domain can leverage it, making the entire system feel smart and responsive.

**This is where Sentinel becomes different from traditional security systems.**

---

**SRE Specification Status**: ✅ Ready for Development  
**Estimated Implementation**: 4-6 weeks  
**Priority**: Phase 1 - Critical Path Item

The Rule Engine is not a nice-to-have. It's the core that makes Sentinel intelligent.
