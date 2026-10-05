package com.sentinel.guard

data class GuardHelpGuide(val topic: String, val title: String, val summary: String)

object GuardHelpGuides {
    val all = listOf(
        GuardHelpGuide("shift_duties", "Starting and ending a shift", "Confirm the assigned site and shift, clock in only when you begin duty, review handover instructions, and clock out after completing the handover."),
        GuardHelpGuide("patrols_checkpoints", "Patrols and checkpoints", "Start the assigned route, follow the site procedure, scan each QR or NFC checkpoint once you are physically there, record exceptions, and complete the patrol only when finished."),
        GuardHelpGuide("report_writing", "Writing a clear report", "Write observable facts in time order. Include what happened, when and where it happened, who was notified, and what action you took. Avoid guesses and emotional wording."),
        GuardHelpGuide("occurrence_book", "Occurrence Book entries", "Record routine events promptly with the correct time and site. State the event, action taken, person notified by role, and any follow-up required."),
        GuardHelpGuide("incidents", "Reporting an incident", "Make the area safe within your training, follow the site escalation procedure, preserve evidence, notify the correct contact, and record verified facts without delaying emergency assistance."),
        GuardHelpGuide("visitors_access", "Visitors and access", "Follow site access rules, verify authorisation, record entry and exit accurately, issue passes as required, and escalate refused or suspicious access without confrontation."),
        GuardHelpGuide("handover", "Giving a proper handover", "Explain outstanding incidents, visitors still on site, equipment or access issues, incomplete patrols, special instructions, and who has already been notified."),
        GuardHelpGuide("emergency_escalation", "Emergency escalation", "For an active emergency, use the site's emergency procedure and contact the appropriate emergency service. The Help Guide does not replace emergency instructions or your control room."),
        GuardHelpGuide("account_security", "Protecting your account", "Use only your own account, keep your password private, sign out on shared devices, and never enter personal, confidential or credential information into the Help Guide.")
    )
}
