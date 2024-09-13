<div>
    <p>Beste $Receiver.FullName,</p>

    <p>
        Je verwijderverzoek is afgekeurd. Je publicatie is niet verwijderd uit SURFsharekit.
    </p>

    <% include Email\Components\Button Text="Ga naar dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink %>
    <% include Email\Defaults\Wiki %>

    <% include Email\Components\divider %>

    <p>Dear $Receiver.FullName,</p>

    <p>
        Your removal request has been declined. Your publication has not been deleted from SURFsharekit.
    </p>

    <% include Email\Components\Button Text="Go to dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink, Lang="en" %>
    <% include Email\Defaults\Wiki Lang="en" %>
</div>