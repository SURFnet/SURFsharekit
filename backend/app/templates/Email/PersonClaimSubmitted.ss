<div>
    <p>Beste $Receiver.FullName,</p>

    <p>
        $AuthorName heeft een verzoek ingediend om $PersonName toe te voegen aan groep $GroupName.
    </p>

    <% include Email\Components\Button Text="Ga naar dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink %>
    <% include Email\Defaults\Wiki %>

    <% include Email\Components\divider %>

    <p>Dear $Receiver.FullName,</p>

    <p>
        $AuthorName requested to add $PersonName to group $GroupName.
    </p>

    <% include Email\Components\Button Text="Go to dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink, Lang="en" %>
    <% include Email\Defaults\Wiki Lang="en" %>
</div>