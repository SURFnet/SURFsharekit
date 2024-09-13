<div>
    <p>Beste $Receiver.FullName,</p>

    <p>
        Er is een fout opgetreden in het door jou geïnitieerde saneerproces op $Time.<br>
        Alle geselecteerde publicaties zijn niet aangepast.
    </p>

    <% include Email\Components\Button Text="Ga naar dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink %>
    <% include Email\Defaults\Wiki %>

    <% include Email\Components\divider %>

    <p>Dear $Receiver.FullName,</p>

    <p>
        An error occured in the remediation process that was initiated by you on $Time. <br>
        All selected publications were left unchanged.
    </p>

    <% include Email\Components\Button Text="Go to dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink, Lang="en" %>
    <% include Email\Defaults\Wiki Lang="en" %>
</div>