<div>
    <p>Beste $Receiver.FullName,</p>

    <p>
        Er is een verzoek tot verwijdering van een $PublicationType.nl voor $Institute waar jij $Role voor bent binnengekomen.
        Klik op onderstaande knop om direct naar je Dashboard te gaan.
    </p>

    <% include Email\Components\Button Text="Ga naar dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink %>
    <% include Email\Defaults\Wiki %>

    <% include Email\Components\divider %>

    <p>Dear $Receiver.FullName,</p>

    <p>
        A request to delete a $PublicationType.en for $Institute for which you are $Role has been made.
        Click on the button to go to your Dashboard.
    </p>

    <% include Email\Components\Button Text="Go to dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink, Lang="en" %>
    <% include Email\Defaults\Wiki Lang="en" %>
</div>