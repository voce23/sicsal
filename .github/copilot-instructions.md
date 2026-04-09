# Copilot Workspace Instructions for SICSAL

## Principles
- **Link, don’t embed**: Reference PLAN_SICSAL.md and code, don’t duplicate details.
- **Follow project conventions**: Use the structure, naming, and patterns in PLAN_SICSAL.md.
- **Respect roles and permissions**: All code must enforce the business rules for superadmin, admin, and registrador.
- **No public registration**: User creation is always by admin/superadmin.
- **Cero justificado**: Any zero in key indicators must trigger justification logic (see PLAN_SICSAL.md, section 5.26, 5.27, 5.3, 10).
- **No hardcoded secrets**: All sensitive data in .env, never in code or repo.
- **Logical deletes**: Use `activo = false` instead of physical deletes.
- **All queries filtered by centro_salud_id** for admin/registrador.
- **Meta INE and coverage calculations**: Use formulas and periods as described in PLAN_SICSAL.md.

## Build & Test
- Use the commands and workflow in PLAN_SICSAL.md section 12 and 13.
- Run migrations and seeders after any DB/model change.
- Use `npm run dev` for asset development, `npm run build` for production.
- Use `php artisan optimize:clear` after config/view changes.

## Architecture & Patterns
- **Filament Resources**: One per module/entity, grouped by navigation as in PLAN_SICSAL.md.
- **Livewire/Filament Widgets**: For dashboards, alerts, and context blocks.
- **Helpers/Services**: For calculations (e.g., PAI scheme, coverage, migration context).
- **Export classes**: Use Maatwebsite\Excel and DomPDF for reports/exports.
- **No direct SQL**: Use Eloquent and query scopes (see Persona.php, PLAN_SICSAL.md section 6).
- **Policies**: Use Laravel policies for model access control.

## Documentation
- For full requirements, see [PLAN_SICSAL.md](PLAN_SICSAL.md) in the repo root.
- For data structure, see migrations and models in `app/Models` and `database/migrations`.
- For business rules, see PLAN_SICSAL.md section 10.
- For development workflow, see PLAN_SICSAL.md section 12-13.

## Example Prompts
- “Create a Filament Resource for [Entidad] following project conventions.”
- “Add a modal for cero justificado in prestaciones.”
- “How is coverage calculated for CAI 1?”
- “Show me the migration for [tabla].”
- “Export the Informe CAI as PDF.”

---

*This file is auto-generated. Update as project conventions evolve. See PLAN_SICSAL.md for all details.*
