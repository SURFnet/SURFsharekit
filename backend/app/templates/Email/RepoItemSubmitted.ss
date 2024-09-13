<div>
    <p>Beste $Receiver.FullName,</p>

    <p>
        Als $Role voor $Institute staat er een $PublicationType.nl in SURFsharekit voor je klaar.
        Klik op onderstaande knop om direct naar je Dashboard te gaan.
    </p>

    <% include Email\Components\Button Text="Ga naar dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink %>
    <% include Email\Defaults\Wiki %>

    <% include Email\Components\divider %>

    <p>Dear $Receiver.FullName,</p>

    <p>
        As $Role for $Institute, there's a $PublicationType.en in SURFsharekit waiting for you Click on the button to go to your Dashboard.
    </p>

    <% include Email\Components\Button Text="Go to dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink, Lang="en" %>
    <% include Email\Defaults\Wiki Lang="en" %>
</div>