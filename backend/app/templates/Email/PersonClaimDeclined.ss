<div>
    <p>Beste $Receiver.FullName,</p>

    <p>
        Je verzoek om $PersonName toe te voegen aan groep $GroupName is afgekeurd.
    </p>
    <p>
        Reden of van afkeuring: $Reason
    </p>

    <% include Email\Components\Button Text="Ga naar dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink %>
    <% include Email\Defaults\Wiki %>

    <% include Email\Components\divider %>

    <p>Dear $Receiver.FullName,</p>

    <p>
        Your request to add $PersonName to group $GroupName has been declined.
    </p>
    <p>
        Reason of decline: $Reason
    </p>

    <% include Email\Components\Button Text="Go to dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink, Lang="en" %>
    <% include Email\Defaults\Wiki Lang="en" %>
</div>