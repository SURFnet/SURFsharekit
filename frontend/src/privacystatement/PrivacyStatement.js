import React from "react";
import {useTranslation} from "react-i18next";
import styled from "styled-components";
import {openSans} from "../Mixins";
import Accordeon from "../components/accordeon/Accordeon";
import {ThemedH1, ThemedH3, ThemedH4, ThemedH5, ThemedP} from "../Elements";

function PrivacyStatement(props) {

    const {t} = useTranslation();

    return (
        <div>
            {
              //props.isDutch ?
                // Dutch version
                <PageContainer>
                    <h1>Privacyverklaring SURFsharekit</h1>
                    <p>Goed dat je de privacyverklaring van de SURF-dienst SURFsharekit bekijkt! We hebben veel aandacht besteed aan de bescherming van jouw persoonsgegevens en in deze privacyverklaring kun je daar meer over te lezen. Als er na het lezen van deze privacyverklaring toch nog vragen, opmerkingen of zorgen zijn, stuur dan gerust een e-mail naar <TextA href="mailto:info@surfsharekit.nl">info@surfsharekit.nl</TextA>.</p>

                    <h2>1 Over SURF</h2>
                    <p>Wij zijn SURF B.V. (hierna: "SURF"), gevestigd aan het Moreelsepark 48, 3511 EP Utrecht. We zijn te bereiken via +31-887873000. SURF is de ICT-samenwerkingsorganisatie van het onderwijs en onderzoek in Nederland. In de coöperatie SURF werken de Nederlandse universiteiten, hogescholen, universitaire medische centra, onderzoeksinstellingen en mbo-instellingen samen aan ICT-innovatie. <TextA href="https://www.surf.nl/over-surf/cooperatie-surf">Meer over de SURF coöperatie</TextA>.</p>

                    <h2>2 Introductie SURFsharekit</h2>
                    <p>SURFsharekit is dé online opslagplaats voor het hoger onderwijs, waar onderzoekspublicaties, afstudeerproducten en open leermaterialen op een duurzaam wijze worden opgeslagen en gedeeld. Onze prioriteit is het bevorderen van <TextA href="https://www.openaccess.nl/">open access</TextA>, waarbij we streven naar maximale toegankelijkheid van materialen. Met SURFsharekit stimuleren we kennisdeling tussen meer dan 30 onderwijsinstellingen in Nederland. SURF heeft deze infrastructuur voor het delen en zoeken van leermaterialen ontwikkeld.</p>

                    <h2>3 Persoonsgegevens</h2>
                    <h3>3.1 Gebruikers en accounts</h3>
                    <h4>3.1.1 Verwerkingen en persoonsgegevens</h4>
                    <p>Voor de werking van SURFsharekit worden persoonsgegevens verwerkt. Zo verwerkt SURF het IP-adres van bezoekers om een goede beschikbaarheid van SURFsharekit te garanderen en om misbruik van SURFsharekit te voorkomen. In opdracht van jouw onderwijsinstelling (verwerkingsverantwoordelijke) verwerkt SURF (verwerker) ook persoonsgegevens voor de volgende doelen:</p>
                    <ul>
                        <li>Het aanmaken, in stand houden en verwijderen van accounts.</li>
                        <li>Het uploaden, downloaden, exporteren, verwijderen, opslaan en aanbieden van materialen.</li>
                        <li>Het koppelen van gebruikers(accounts) aan materialen.</li>
                        <li>Het sturen van notificaties aan gebruikers.</li>
                        <li>Het ondersteunen van gebruikers bij problemen en vragen.</li>
                        <li>Het maken van frequente backups voor de bovenstaande doelen.</li>
                    </ul>
                    <p>De verwerkte persoonsgegevens zijn hieronder in meer detail uitgewerkt.</p>

                    <table>
                        <thead>
                        <tr>
                            <th>Persoonsgegeven</th>
                            <th>Verantwoordelijke</th>
                            <th>Verwerker</th>
                            <th>Doel</th>
                            <th>Retentie</th>
                            <th>Grondslag</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>IP-adres</td>
                            <td>SURF</td>
                            <td>Zooma</td>
                            <td>Technisch noodzakelijk voor een goede werking van het internet. Daarnaast noodzakelijk voor een goede beschikbaarheid en de voorkoming van misbruik.</td>
                            <td>~30 dagen</td>
                            <td>Gerechtvaardigd belang</td>
                        </tr>
                        <tr>
                            <td>Voornaam</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt om de naam van de gebruiker weer te geven.</td>
                            <td>Accountduur</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Achternaam</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt om de naam van de gebruiker weer te geven.</td>
                            <td>Accountduur</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>E-mailadres</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt om de gebruiker te identificeren en valideren en om notificaties te versturen.</td>
                            <td>Accountduur</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Onderwijsinstelling</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt voor identificatie en validatie van gebruikers, om de rechten van een gebruiker vast te stellen en om instellingstemplates beschikbaar te stellen aan gebruikers van die instelling.</td>
                            <td>Accountduur</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Rol (bijv. student)</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Noodzakelijk om de juiste rol aan gebruikers toe te kennen en om toegang te geven tot specifieke rolfunctionaliteiten.</td>
                            <td>Accountduur</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Unieke identifiers</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Noodzakelijk voor het identificeren van unieke personen en het koppelen van verschillende persoonsgegevens aan een gebruikersaccount/persoon.</td>
                            <td>Accountduur</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Lidmaatschappen SURFconext Teams</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Op basis van de SURFconext Teamslidmaatschappen wordt toegang tot materialen toegekend.</td>
                            <td>Accountduur</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Opleiding</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Hiermee wordt studenten de mogelijkheid geboden om materialen aan de eigen opleiding aan te bieden.</td>
                            <td>Accountduur</td>
                            <td>Instelling</td>
                        </tr>
                        </tbody>
                    </table>

                    <p>SURF verwerkt je accountgegevens totdat het gebruikersaccount wordt verwijderd + de retentie voor de automatische backups. Backups worden één maand bewaard. Dit betekent dat de persoonsgegevens na het verwijderen van een account nog één maand in backups voorkomen.</p>

                    <h4>3.1.2 Verstrekking van persoonsgegevens</h4>
                    <p>Medewerkers van je onderwijsinstelling hebben toegang tot je accountgegevens. SURF en diens beheerpartij Zooma B.V. hebben toegang tot alle persoonsgegevens. De persoonsgegevens worden niet aan andere derde partijen verstrekt.</p>

                    <h3>3.2 Personen die voorkomen in materialen</h3>
                    <h4>3.2.1 Verwerkingen en persoonsgegevens</h4>
                    <p>In opdracht van jouw onderwijsinstelling (verwerkingsverantwoordelijke) verwerkt SURF (verwerker) ook persoonsgegevens voor het uploaden, downloaden, exporteren, verwijderen, opslaan en aanbieden van materialen. Dit zijn gegevens van personen die voorkomen in materialen, bijvoorbeeld van de auteur.</p>
                    <p>De verwerkte persoonsgegevens zijn hieronder in meer detail uitgewerkt.</p>

                    <table>
                        <thead>
                        <tr>
                            <th>Persoonsgegeven</th>
                            <th>Verantwoordelijke</th>
                            <th>Verwerker</th>
                            <th>Doel</th>
                            <th>Retentie</th>
                            <th>Grondslag</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Voorletter(s)</td>
                            <td>Instelling</td>
                            <td>SURF</td>


                            <td>Wordt gebruikt om (de gegevens van) een auteur te koppelen aan een materiaal.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Voornaam</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt om (de gegevens van) een auteur te koppelen aan een materiaal.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Achternaam</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt om (de gegevens van) een auteur te koppelen aan een materiaal.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Titulatuur</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt om (de gegevens van) een auteur te koppelen aan een materiaal.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Academische titel</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt om (de gegevens van) een auteur te koppelen aan een materiaal.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Organisatie</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt om (de gegevens van) een auteur te koppelen aan een materiaal.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Functie</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt om (de gegevens van) een auteur te koppelen aan een materiaal.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>E-mailadres</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt voor samenwerking en het opnemen van professioneel contact.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Telefoonnummer</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Wordt gebruikt voor samenwerking en het opnemen van professioneel contact.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Alias</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Voorkeursnaam van een auteur, die af kan wijken van de geboortenaam.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Rol bij publicatie</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>De rol die de auteur had ten tijde van het schrijven van het materiaal.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Cijfer</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Het cijfer wordt gebruikt om te bepalen of een scriptie voldoende kwaliteit heeft om gepubliceerd te mogen worden. Dit cijfer wordt nooit aan gebruikers getoond.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Opmerkingen</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Er is een mogelijkheid om een opmerking in een vrij in te vullen veld bij het materiaal te plaatsen.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>DAI</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Een nationaal unieke identifier die gebruikt wordt om Nederlandse auteurs te identificeren.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>ORCID</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Een internationaal unieke identifier die gebruikt wordt om auteurs van wetenschappelijke werken te identificeren.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>ISNI</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Een internationale unieke identifier die gebruikt wordt om personen die bijdragen aan creatieve werken te identificeren.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        <tr>
                            <td>Instellingsidentifier</td>
                            <td>Instelling</td>
                            <td>SURF</td>
                            <td>Een instellingsspecifieke identifier die gebruikt wordt om auteurs binnen een instelling te identificeren.</td>
                            <td>Zolang materiaal beschikbaar is</td>
                            <td>Instelling</td>
                        </tr>
                        </tbody>
                    </table>

                    <h4>3.2.2 Verstrekking van persoonsgegevens</h4>
                    <p>Medewerkers van je onderwijsinstelling hebben toegang tot je accountgegevens. SURF en diens beheerpartij heeft toegang tot alle persoonsgegevens. De persoonsgegevens worden niet aan andere derde partijen verstrekt.</p>

                    <h3>3.3 Statistieken</h3>
                    <h4>3.3.1 Verwerkingen en persoonsgegevens</h4>
                    <p>SURFsharekit gebruikt <TextA href="https://piwik.pro/">Piwik PRO</TextA> om statistieken bij te houden. Zo kunnen we zien hoe bezoekers SURFsharekit gebruiken en krijgen we informatie waarmee we de website kunnen verbeteren. Piwik PRO verwerkt hiervoor het IP-adres en een browser user agent. Van het IP-adres wordt alleen het eerste deel (ook wel octet) opgeslagen. Wanneer je browser is ingesteld om niet gevolgd te worden zal Piwik PRO geen data verzamelen van je bezoek. SURFsharekit gebruikt de statistieken niet om bezoekers te identificeren, maar uitsluitend om de website te kunnen verbeteren.</p>

                    <table>
                        <thead>
                        <tr>
                            <th>Persoonsgegeven</th>
                            <th>Verantwoordelijke</th>
                            <th>Verwerker</th>
                            <th>Doel</th>
                            <th>Retentie</th>
                            <th>Grondslag</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>IP-adres</td>
                            <td>SURF</td>
                            <td>Piwik PRO</td>
                            <td>Website verbeteren aan de hand van statistieken over het gebruik van SURFsharekit.</td>
                            <td>Sessieduur</td>
                            <td>Gerechtvaardigd belang</td>
                        </tr>
                        <tr>
                            <td>Browser User Agent</td>
                            <td>SURF</td>
                            <td>Piwik PRO</td>
                            <td>Website verbeteren aan de hand van statistieken over het gebruik van SURFsharekit.</td>
                            <td>Sessieduur</td>
                            <td>Gerechtvaardigd belang</td>
                        </tr>
                        </tbody>
                    </table>

                    <p>Voor deze verwerking worden ook cookies geplaatst. Meer informatie over de cookies die Piwik plaatst vind je in de <TextA href="https://www.surf.nl/cookieverklaring">cookieverklaring</TextA> van SURF .</p>

                    <h4>3.3.2 Verstrekking van persoonsgegevens</h4>
                    <p>Piwik PRO heeft als verwerker van SURF toegang tot deze persoonsgegevens.</p>

                    <h2>4 Cookies</h2>
                    <p>SURFsharekit plaatst functionele cookies om op basis van de toegekende rollen en rechten de juiste informatie te kunnen verstrekken aan de gebruiker. Ook zorgen deze cookies ervoor dat de gebruiker tijdelijk ingelogd blijft.</p>

                    <h2>5 Beveiliging</h2>
                    <p>Onder andere de volgende veiligheidsmaatregelen zijn getroffen om de persoonsgegevens te beschermen:</p>
                    <ul>
                        <li>Applicatie en ontwikkeling (onder beheer van Zooma)</li>
                        <ul>
                            <li>Moderne versleuteling op alle communicatie tussen systemen (servers, clients, applicaties, componenten etc.)</li>
                            <li>Het ontwikkelproces van Zooma is ISO-gecertificeerd</li>
                            <li>Buiten productiesysteem en de bijbehorende

                                back-ups worden nergens persoonsgegevens opgeslagen</li>
                            <li>Toegangscontroles geschieden door, ten minste, gebruik te maken van sterke wachtwoorden. Waar mogelijk wordt gebruik gemaakt van 2FA.</li>
                            <li>Opslag van de persoonsgegevens gebeurt op servers van SURF (SVP), beheerd door Prolocation (zie server en serverbeheer)</li>
                            <li>Geen opslag van persoonsgegevens op papier, USB‐sticks of in onbeveiligde losse bestanden</li>
                            <li>Door Verwerker gebruikte software en OS zijn up‐to‐date</li>
                            <li>Er wordt gebruik gemaakt van up‐to‐date firewalls, virusscanners en anti‐virussoftware</li>
                            <li>In het bedrijfspand van Verwerker wordt gebruik gemaakt van encrypted VPN‐verbindingen</li>
                            <li>Indien redelijkerwijs mogelijk wordt gebruik gemaakt van pseudonimisering of anonimisering</li>
                            <li>Uitgangspunt bij de ontwikkeling is privacy en security by design en default (default settings)</li>
                        </ul>
                        <li>Server en serverbeheer (Prolocation)</li>
                        <ul>
                            <li>ISO 9001, 27001 en NEN7510-gecertificeerd sinds augustus 2018 volgens de nieuwste internationale normering voor informatiebeveiliging en -management, ISO 27001. Op het gebied van informatiebeveiliging is Prolocation gecertificeerd voor alle beheerde technische bedrijfsmiddelen en alle softwareproducten die zij aanbieden:</li>
                            <ul>
                                <li>Veiligheid van informatie en data op de infrastructuur van Prolocation, waarbij alle data strikt in de Europese Economische Regio (EER) opgeslagen wordt</li>
                                <li>Zekerheid over het voldoen aan wet- en regelgeving omtrent informatiebeveiligingen privacy, zowel nu als in de toekomst</li>
                                <li>Een sterk technisch fundament op basis van best practices, regelmatig getest door onafhankelijke organisaties middels security- en penetratietesten</li>
                                <li>Uitsluitend geautoriseerde toegang tot data, zowel fysiek als online</li>
                                <li>Doorlopende monitoring en verbetering van processen en procedures</li>
                            </ul>
                            <li>Prolocation heeft een uitgebreide set van veiligheidsprocedures:</li>
                            <ul>
                                <li>Medewerkers en partners worden geïnformeerd en getraind over deze policies en afwijkingen daarvan worden geregistreerd.</li>
                                <li>Nieuwe medewerkers nemen deel aan een bewustzijnstrainingen rondom best practices, ISO 9001, 27001, NEN7510 en databeveiliging. Intern wordt regelmatig informatie gedeeld over nieuwe ontwikkelingen, uitkomsten uit steekproeven en leermomenten uit incidenten. Het management van Prolocation is hier nauw bij betrokken</li>
                                <li>Werken op basis van “Least privilege”. Dat houdt in dat een gebruiker enkel toegang heeft tot die bronnen die nodig zijn voor het uitvoeren van de gevraagde taken en niet meer dan dat.</li>
                                <li>Alle systemen, netwerken en hardware worden 24/7 actief gemonitord. Bij (mogelijke) problemen of pogingen van derden om binnen te komen, worden diverse alarmsystemen en notificaties naar verschillende niveaus ingezet.</li>
                                <li>Adequaat updatebeleid om snel beveiligingsupdates door te voeren (periodieke security updates)</li>
                            </ul>
                            <li>Web Application Firewall om bekende en nieuwe aanvallen te beperken</li>
                            <li>Toegang tot servers alleen via jumphosts</li>
                            <li>Periodieke back‐ups en centrale logging</li>
                        </ul>
                    </ul>

                    <h2>7 Jouw rechten</h2>
                    <p>Je hebt de volgende rechten met betrekking tot je persoonsgegevens:</p>
                    <ul>
                        <li>Je kunt een verzoek indienen tot wijziging, aanvulling of verwijdering van je gegevens wanneer deze onjuist of niet (meer) relevant zijn.</li>
                        <li>Je kunt een verzoek indienen om inzage te verkrijgen in de gegevens die we van jou verwerken.</li>
                        <li>Je kunt bezwaar maken tegen verwerking van je gegevens, als we je gegevens verwerken op basis van een eigen gerechtvaardigd belang of op basis van de uitvoering van een taak van algemeen belang.</li>
                        <li>Je kunt een verzoek indienen tot beperking van de verwerking van je gegevens ten aanzien van de verwerking van gegevens waartegen je bezwaar hebt gemaakt, die je onrechtmatig acht, waarvan je de juistheid van de persoonsgegevens hebt betwist of wanneer we de persoonsgegevens niet meer nodig hebben, maar je ze nodig hebt in het kader van een rechtsvordering.</li>
                        <li>Je kunt een overzicht, in een gestructureerde en gangbare vorm opvragen van de gegevens die we van jou verwerken en je hebt het recht op overdraagbaarheid van deze gegevens naar een andere dienstverlener.</li>
                        <li>Je kunt de door jou gegeven toestemming voor het verwerken van je persoonsgegevens intrekken. Het intrekken van je toestemming doet echter geen afbreuk aan de rechtmatigheid van de verwerking op basis van je toestemming vóór de intrekking daarvan.</li>
                        <li>Als je van mening bent dat SURF niet goed omgaat met je persoonsgegevens kun je een klacht indienen bij SURF.</li>
                        <li>Als jij en/of SURF er echter samen niet samen uitkomen en het antwoord leidt tot een acceptabel resultaat, dan heb je het recht om een klacht hierover in te dienen bij de Autoriteit Persoonsgegevens. Meer informatie over de Autoriteit Persoonsgegevens en het indienen van klachten vind je op de website van de <TextA href="https://autoriteitpersoonsgegevens.nl/">Autoriteit Persoonsgegevens</TextA>.</li>
                    </ul>
                    <p>Om deze rechten uit te kunnen oefenen, kun je contact opnemen met jouw eigen onderwijsinstelling. Twijfel je waar je terecht kunt met een verzoek of klacht? Stuur dan een e-mail naar <TextA href="mailto:info@surfsharekit.nl">info@surfsharekit.nl</TextA> zodat we je hierbij kunnen helpen.</p>

                    <h2>8 Wijzigingen privacyverklaring</h2>
                    <p>Er kunnen wijzigingen worden aangebracht in deze privacyverklaring. We raden je daarom aan om deze privacyverklaring geregeld te raadplegen. Deze privacyverklaring is voor het laatst gewijzigd op 4 juni 2024.</p>
                </PageContainer>
            }
        </div>
    )
}

const H1 = styled(ThemedH1)`
  padding-bottom: 10px;
`

const H3 = styled(ThemedH3)`
  padding-bottom: 10px;
`

const H4 = styled(ThemedH4)`
  padding-bottom: 10px;
`

const H5 = styled(ThemedH5)`
  padding-bottom: 10px;
`

const P = styled(ThemedP)`
  line-height: 22px;
`

const PageContainer = styled.div`
  margin: 0 20%;
  line-height: 1.6;
  h1, h2, h3, h4 {
    margin: 20px 0 0 0;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
  }
  table, th, td {
    border: 1px solid #ddd;
  }
  th, td {
    padding: 12px;
    text-align: left;
  }
  th {
    background-color: #f4f4f4;
  }
  @media (max-width: 1024px) {
    margin: 0 10%;
  }

  @media (max-width: 768px) {
    margin: 0 5%;
  }
  & > div {
    margin-bottom: 30px;

    &:last-child {
      margin-bottom: 0;
    }
  }
`;

const AccordeonP = styled(ThemedP)`
  color: white;
`

const List = styled.ul`
  padding: 0;
`;

const TextA = styled.a`
  color: #0077c8 !important;
  text-decoration: underline !important;
`

const ListItem = styled.li`
  ${openSans};
  font-size: 12px;
  margin-bottom: 10px;
  line-height: 20px;
`;

const Contact = styled.div`
  font-size: 12px;
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  width: 200px;
  gap: 3px;
`

export default PrivacyStatement;