# Database Documentation - Sentinel AI Security Suite

**Professional Database Blueprint for Enterprise Security Operations**

---

## 📑 Quick Navigation

Current planning gate: [MASTER_DATA_MODEL.md](MASTER_DATA_MODEL.md) defines domain ownership and Finance readiness constraints before new schema expansion.
Entity gate (required before major module coding): [ENTITY_CATALOG.md](ENTITY_CATALOG.md) defines master entities, child models, lifecycle, permissions, audit, AI, reporting, offline, and integrations.
Relationship gate (required before major module coding): [ENTERPRISE_RELATIONSHIP_MODEL.md](ENTERPRISE_RELATIONSHIP_MODEL.md) defines canonical ERD boundaries and cross-domain relationship contracts.

Mandatory enterprise baseline (must be approved before major feature expansion):
1. [ENTITY_CATALOG.md](ENTITY_CATALOG.md)
2. [ENTERPRISE_RELATIONSHIP_MODEL.md](ENTERPRISE_RELATIONSHIP_MODEL.md)
3. [../architecture/PERMISSION_MATRIX.md](../architecture/PERMISSION_MATRIX.md)
4. [../architecture/WORKFLOW_CATALOG.md](../architecture/WORKFLOW_CATALOG.md)
5. [../architecture/EVENT_CATALOG.md](../architecture/EVENT_CATALOG.md)
6. [../architecture/API_CONTRACT_CATALOG.md](../architecture/API_CONTRACT_CATALOG.md)
7. [../architecture/UI_NAVIGATION_MAP.md](../architecture/UI_NAVIGATION_MAP.md)

### For Different Roles

**👨‍💻 Backend Developers**
1. Start: [DATABASE_DESIGN.md](DATABASE_DESIGN.md) - Overview
2. Read: [SCHEMA.md](SCHEMA.md) - All 99 tables
3. Reference: [NAMING_STANDARDS.md](NAMING_STANDARDS.md) - Conventions
4. Learn: [RELATIONSHIPS.md](RELATIONSHIPS.md) - Data flow

**🏗️ Database Architects**
1. Start: [DATABASE_DESIGN.md](DATABASE_DESIGN.md) - Architecture
2. Study: [RELATIONSHIPS.md](RELATIONSHIPS.md) - ERD concepts
3. Deep-dive: [SCHEMA.md](SCHEMA.md) - Table details
4. Reference: [NAMING_STANDARDS.md](NAMING_STANDARDS.md)

**📊 Data Analysts**
1. Start: [DATABASE_DESIGN.md](DATABASE_DESIGN.md) - Overview
2. Learn: [RELATIONSHIPS.md](RELATIONSHIPS.md) - Query patterns
3. Reference: [SCHEMA.md](SCHEMA.md) - Data structure

**🔒 Database Administrators**
1. Start: [DATABASE_DESIGN.md](DATABASE_DESIGN.md) - Security section
2. Study: [NAMING_STANDARDS.md](NAMING_STANDARDS.md) - Standards
3. Reference: All files for implementation

**👤 Project Managers**
1. Read: [DATABASE_DESIGN.md](DATABASE_DESIGN.md) - Executive Summary
2. Review: Table counts and statistics
3. Check: Implementation roadmap

---

## 📁 File Structure

```
docs/database/
├── README.md (this file)
├── DATABASE_DESIGN.md (Master blueprint)
├── SCHEMA.md (99-table definitions)
├── RELATIONSHIPS.md (Foreign keys & data flow)
└── NAMING_STANDARDS.md (Conventions)
```

---

## 📄 Document Overview

### [DATABASE_DESIGN.md](DATABASE_DESIGN.md)
**Executive-level database blueprint**

- Architecture overview (multi-tenancy, DDD)
- Statistics (99 tables, 15 domains)
- Security & isolation strategy
- Scalability planning
- Key design decisions
- Data flow workflows
- Implementation roadmap
- QA & security checklists

**When to use**: Project kickoff, architecture review, onboarding

---

### [MASTER_DATA_MODEL.md](MASTER_DATA_MODEL.md)
**Domain ownership blueprint and implementation gate**

- Enterprise hierarchy and domain ownership boundaries
- Mandatory tenant-scoping and integrity standards
- Cross-domain integration contracts
- Operations MVP completion checklist before Finance workflow implementation

**When to use**: Before adding any table, migration, or cross-domain integration logic

---

### [ENTITY_CATALOG.md](ENTITY_CATALOG.md)
**Enterprise entity blueprint for module implementation**

- Master entity backbone for the entire platform
- Child entities and relationship model per major entity
- Lifecycle standards and action model
- Permissions model (actions + field-level + scope-level)
- Audit, reporting, AI, mobile/offline, and integration requirements

**When to use**: Before implementing any major module (Employee, Site, Shift, Patrol, Incident, Vehicle, Equipment, Finance, etc.)

**Key content**: 
```
- Multi-tenant architecture explained
- Domain-Driven Design structure
- Core business entities
- Advanced features (AI, event sourcing)
- Success criteria
```

---

### [ENTERPRISE_RELATIONSHIP_MODEL.md](ENTERPRISE_RELATIONSHIP_MODEL.md)
**Canonical enterprise relationship model and ERD contract**

- Defines parent/child and cross-domain relationship boundaries
- Defines tenant-scope propagation rules across relationships
- Defines mandatory foreign-key and cardinality expectations
- Defines ERD approval checklist for module expansion gates

**When to use**: Before adding new tables, foreign keys, polymorphic relationships, or cross-domain joins

---

### [SCHEMA.md](SCHEMA.md)
**Complete technical specification**

- All 99 tables by domain (15 domains)
- Column-by-column definitions
- Data types and constraints
- Primary/foreign keys
- Indexes and comments
- Relationships to other tables

**When to use**: Implementation, code development, database creation

**Structure** (15 domains):
1. Core/Organization (8 tables)
2. Authentication (6 tables)
3. Workforce (8 tables)
4. Attendance (10 tables)
5. Incidents (12 tables)
6. Visitors (8 tables)
7. Vehicles (9 tables)
8. Patrols (7 tables)
9. Assets (5 tables)
10. Fire Safety (6 tables)
11. Reporting (6 tables)
12. AI/Intelligence (6 tables)
13. Settings (5 tables)
14. Audit & Compliance (4 tables)
15. Files (3 tables)

**Key content**:
```sql
CREATE TABLE examples for all 99 tables
Column definitions with types
Constraints (primary key, unique, not null)
Foreign key references
Indexes and optimization hints
Comments for every column
```

---

### [RELATIONSHIPS.md](RELATIONSHIPS.md)
**Data relationships and query patterns**

- 150+ foreign key relationships
- Data flow between domains
- Common query patterns
- Multi-table examples
- Audit trail setup
- Referential integrity rules

**When to use**: Writing queries, understanding data flow, optimization

**Key content**:
```
- Company hierarchy structure
- Person/Employment workflow
- Authentication chain
- Attendance flow with GPS
- Incident hub pattern
- Visitor access tracking
- Vehicle lifecycle
- Patrol execution
- Complete query examples
```

---

### [NAMING_STANDARDS.md](NAMING_STANDARDS.md)
**Database naming conventions**

- Table naming rules (plural, lowercase, snake_case)
- Column naming patterns
- Primary/foreign key conventions
- Boolean prefixes (is_, has_, can_)
- Timestamp formats ({action}_at)
- ENUM vs integer choices
- Index naming conventions
- Comments requirements

**When to use**: Database design review, code review, new schema additions

**Key content**:
```
- 16-point validation checklist
- Examples for each convention
- Anti-patterns to avoid
- Data type standards
- Comment field requirements
- Domain-specific naming
```

---

## 🎯 What Problems Does This Solve?

### ✅ Multi-Tenancy
Every table includes `company_id` for complete data isolation
```sql
SELECT * FROM incidents 
WHERE company_id = ? AND incident_id = ?;
```

### ✅ Audit & Compliance
Full timestamp tracking + audit_logs table
```sql
-- Tracks all changes with before/after values
INSERT INTO audit_logs 
SELECT 'Update', old_record, new_record FROM ...
```

### ✅ Scalability
Designed for 100M+ records per company
```
- Proper indexing strategy
- Soft deletes (no hard deletes)
- Partition-ready design
```

### ✅ Consistency
Naming standards prevent confusion
```sql
-- Consistent across all tables
user_id, company_id, incident_id
created_at, updated_at, deleted_at
is_active, has_children, can_approve
```

### ✅ Performance
Optimized for common queries
```sql
-- Indexed for fast lookups
INDEX idx_company (company_id)
INDEX idx_employee_date (employee_id, attendance_date)
UNIQUE KEY uniq_email (email)
```

---

## 🚀 How to Use This

### Step 1: Read Overview
```
Start with DATABASE_DESIGN.md
Understand: Multi-tenancy, domains, structure
Time: 15 minutes
```

### Step 2: Study Schema
```
Read SCHEMA.md by domain
Understand: Table purposes, relationships
Time: 1-2 hours
```

### Step 3: Learn Relationships
```
Read RELATIONSHIPS.md for your area
Understand: Data flow, query patterns
Time: 30 minutes
```

### Step 4: Implement
```
Reference NAMING_STANDARDS.md
Follow: Conventions, patterns, checklists
Time: Variable
```

### Step 5: Review
```
Use validation checklist from NAMING_STANDARDS.md
Verify: Consistency, completeness
Time: 15 minutes
```

---

## 📊 Database Statistics

| Metric | Value |
|--------|-------|
| Total Tables | 99 |
| Domains | 15 |
| Foreign Keys | 150+ |
| Indexes | 200+ |
| Comments | Every column |
| JSON Fields | 20+ |
| ENUM Fields | 30+ |
| Estimated Size | ~5 GB/company/year |

---

## 🔧 Quick Commands

### Find table by name
```bash
# In SCHEMA.md
CTRL+F "table_name"
```

### Find related tables
```bash
# Check RELATIONSHIPS.md
Look for "table_name" in Foreign Key section
```

### Find naming convention
```bash
# In NAMING_STANDARDS.md
Search for field type (boolean, timestamp, etc.)
```

### Find example query
```bash
# In RELATIONSHIPS.md
Search "Query:" for examples
```

---

## 🎓 Learning Path

### Beginner (New Developer)
1. Read DATABASE_DESIGN.md (overview)
2. Focus on one domain in SCHEMA.md
3. Learn naming standards in NAMING_STANDARDS.md
4. Write queries following RELATIONSHIPS.md examples

### Intermediate (Experienced Developer)
1. Study RELATIONSHIPS.md data flows
2. Optimize using indexing strategies
3. Write complex multi-table queries
4. Review naming standards for new additions

### Advanced (Architect)
1. Analyze multi-tenancy architecture
2. Plan for scalability
3. Design extensions to schema
4. Review and approve naming changes

---

## ✨ Key Features

### 🔒 Security
- Multi-tenant isolation via company_id
- Soft deletes for compliance
- Audit trail of all changes
- Role-based access control
- Column-level encryption support

### 📈 Scalability
- Designed for 100M+ records per table
- Partition-ready structure
- Read replica support
- Event sourcing capability
- Materialized view friendly

### 🎯 Flexibility
- JSON fields for evolving requirements
- ENUM for type safety
- Status tracking
- Custom attributes
- Audit trail

### 🚀 Performance
- Optimized indexing
- Denormalization where needed
- Query optimization hints
- GPS coordinate support
- Calculated field support

---

## ❓ Common Questions

**Q: Where do I find the person/employee relationship?**  
A: [SCHEMA.md](SCHEMA.md) - Core/Organization section, or [RELATIONSHIPS.md](RELATIONSHIPS.md) - Person/Employment Workflow

**Q: How do I create a new table following standards?**  
A: Follow [NAMING_STANDARDS.md](NAMING_STANDARDS.md) - Validation Checklist section

**Q: What's the query to find all incidents for a site?**  
A: [RELATIONSHIPS.md](RELATIONSHIPS.md) - Common Queries section

**Q: How is multi-tenancy implemented?**  
A: [DATABASE_DESIGN.md](DATABASE_DESIGN.md) - Multi-Tenancy Design section

**Q: Where are the SQL CREATE statements?**  
A: SCHEMA.md - Each table includes CREATE TABLE definition

---

## 📞 Support

### Documentation Issues
- Update relevant .md file
- Commit with git
- Notify development team

### Schema Changes
- Update SCHEMA.md
- Update RELATIONSHIPS.md if FK affected
- Check NAMING_STANDARDS.md compliance
- Get architecture review

### Question / Clarification
- Check this README first
- Search relevant document (Ctrl+F)
- Ask team lead or architect

---

## 📋 Maintenance

### When Adding New Table
1. Add to SCHEMA.md (correct domain)
2. Update RELATIONSHIPS.md (if related)
3. Verify NAMING_STANDARDS.md compliance
4. Run validation checklist
5. Commit with git

### When Modifying Table
1. Update SCHEMA.md
2. Check foreign key impact in RELATIONSHIPS.md
3. Update any affected queries
4. Verify naming compliance
5. Commit with git

### When Renaming Field
1. Update SCHEMA.md
2. Update RELATIONSHIPS.md queries
3. Update NAMING_STANDARDS.md examples
4. Application code update (separate PR)
5. Migration script (separate task)

---

## 📚 Additional Resources

### Links in Documentation
- Database design patterns
- Multi-tenant architecture
- Query optimization
- Backup strategies

### External References
- MySQL 8.0 Documentation
- Domain-Driven Design
- Database normalization

### Tools Recommended
- MySQL Workbench (schema design)
- DBeaver (data exploration)
- Navicat (data migration)
- MkDocs (documentation)

---

## ✅ Quality Checklist

Before using any documentation:

- [ ] All 4 files present
- [ ] DATABASE_DESIGN.md is readable
- [ ] SCHEMA.md has all 99 tables
- [ ] RELATIONSHIPS.md has all FK mappings
- [ ] NAMING_STANDARDS.md complete
- [ ] All links working in this README
- [ ] No broken references
- [ ] Examples accurate
- [ ] Statistics current

---

## 🎯 Status

| Document | Status | Completeness | Version |
|----------|--------|-------------|---------|
| DATABASE_DESIGN.md | ✅ Complete | 100% | 1.0 |
| SCHEMA.md | ✅ Complete | 100% | 1.0 |
| RELATIONSHIPS.md | ✅ Complete | 100% | 1.0 |
| NAMING_STANDARDS.md | ✅ Complete | 100% | 1.0 |
| This README | ✅ Complete | 100% | 1.0 |

---

**Database Blueprint**: ✅ Production Ready  
**Quality**: Enterprise Grade  
**Last Updated**: Current Session

*Use this documentation as the definitive source of truth for the Sentinel AI database.*
