import React from "react";
import {useTranslation} from "react-i18next";
import styled from "styled-components";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {nunitoBlack, nunitoBold, nunitoExtraBold, openSans, spaceCadet} from "../Mixins";
import Accordeon from "../components/accordeon/Accordeon";
import {ThemedA, ThemedH1, ThemedH2, ThemedH3, ThemedH4, ThemedH5, ThemedP} from "../Elements";


function CookieStatement(props){
    const {t} = useTranslation();

    return (
        <div>
            { props.isDutch ?
                // Dutch version
                <PageContainer>
                    <div>
                        <H1>Cookie verklaring</H1>
                    </div>
                    <div>
                        <H5>Onze website plaatst cookies op je computer - na jouw toestemming. Cookies laten de site goed functioneren en bieden ons informatie waarmee we je gebruikservaring kunnen verbeteren. Ook derden kunnen via onze website cookies plaatsen. In deze cookieverklaring lees je welke cookies we plaatsen.</H5>
                    </div>
                    <div>
                        <H3>Over cookies</H3>
                        <P>Op onze website gebruiken wij cookies: kleine bestanden die verstuurd worden door een internetserver en die worden opgeslagen op je computer. Ook via derden die wij hebben ingeschakeld, worden cookies geplaatst.</P>
                    </div>
                    <div>
                        <H3>Beveiligingsmaatregelen</H3>
                        <P>Wanneer je onze website voor het eerst bezoekt, zie je onderaan het venster de mededeling "SURF plaatst functionele cookies om deze website goed te laten werken. Ook plaatsen we analytische cookies om de website te verbeteren. De data wordt waar mogelijk geanonimiseerd." Deze cookies plaatsen we zonder jouw uitdrukkelijke toestemming.</P>
                        <P>Daarnaast vragen we je toestemming voor het plaatsen van cookies van derden. Ingesloten content (zoals YouTube-video's) kan cookies van derde partijen bevatten. Door "Accepteer" te klikken, geef je toestemming voor het plaatsen van cookies van derden.</P>
                        <P>Je kunt de cookies weer verwijderen via je browserinstellingen. Daar kun je ook instellen welk soort cookies op je computer geplaatst worden. De meeste cookies hebben een houdbaarheidsdatum. Dat betekent dat ze na een bepaalde periode automatisch verlopen en geen gegevens van uw site-bezoek meer registeren. Je kunt er ook voor kiezen de cookies handmatig te verwijderen via je browserinstellingen.</P>
                    </div>
                    <div>
                        <H3>Afspraken met derden over cookies</H3>
                        <P>Met derden die cookies plaatsen hebben wij afspraken gemaakt over het gebruik van de cookies en applicaties. Toch heeft SURF geen volledige controle op wat de aanbieders van deze applicaties zelf met de cookies doen wanneer zij deze uitlezen. In de privacyverklaringen van deze partijen lees je meer over deze applicaties en hoe zij met cookies omgaan (let op: deze kunnen regelmatig wijzigen).</P>
                    </div>
                    <div>
                        <H3>Typen cookies op surf.nl</H3>
                        <H4>Functionele cookies</H4>
                        <P>Onze website gebruikt een aantal functionele cookies, die ervoor zorgen dat de website correct functioneert:</P>
                        <List>
                            <ListItem>cookieconsent_status: noodzakelijk om op te slaan wat je cookievoorkeur is en is 12 maanden geldig</ListItem>
                        </List>
                    </div>
                    <div>
                        <H3>Waarvoor gebruiken we je gegevens?</H3>
                        <Accordeon title={"SURF plaatst de volgende Matomocookies op je computer"}>
                            <div>
                                <AccordeonP>Als je je via onze website aanmeldt voor een evenement, vragen we de volgende persoonsgegevens:</AccordeonP>
                                <List>
                                    <ListItem>Voor- en achternaam (verplicht veld)</ListItem>
                                    <ListItem>E-mailadres (verplicht veld)</ListItem>
                                    <ListItem>Organisatie waar je werkt (verplicht veld)</ListItem>
                                    <ListItem>Organisatiesector waarin je werkt</ListItem>
                                    <ListItem>Functie</ListItem>
                                    <ListItem>Betalingsgegevens</ListItem>
                                    <ListItem>Dieetvoorkeuren</ListItem>
                                    <ListItem>Eventuele overige informatie die je vrijwillig invult</ListItem>
                                </List>
                            </div>
                        </Accordeon>
                    </div>
                    <div>
                        <H3>Wijziging cookieverklaring</H3>
                        <P>SURF behoudt zich het recht voor om wijzigingen aan te brengen in deze cookieverklaring. We raden je aan om deze cookieverklaring geregeld te raadplegen, zodat je van deze wijzigingen op de hoogte bent.</P>
                    </div>
                    <div>
                        <H3>Privacyverklaring</H3>
                        <P><TextA href="/cookies">Lees ook onze privacyverklaring.</TextA> Daar lees je hoe we omgaan met je persoonsgegevens, hoe wij jouw gegevens beveiligen en welke rechten jij hebt met betrekking tot jouw gegevens.</P>
                    </div>
                    <div>
                        <H3>Contactgegevens SURF</H3>
                        <ThemedH4>Algemeen</ThemedH4>
                        <Contact>
                            <P>SURF</P>
                            <P>Moreelsepark 48</P>
                            <P>3511 EP  Utrecht</P>
                            <TextA href={"communicatie@surf.nl"}>communicatie@surf.nl</TextA>
                            <P>31 88 787 30 00</P>
                        </Contact>
                    </div>
                </PageContainer>
                :
                // English version
                <PageContainer>
                    <div>
                        <H1>Cookie verklaring</H1>
                    </div>
                    <div>
                        <H5>Onze website plaatst cookies op je computer - na jouw toestemming. Cookies laten de site goed functioneren en bieden ons informatie waarmee we je gebruikservaring kunnen verbeteren. Ook derden kunnen via onze website cookies plaatsen. In deze cookieverklaring lees je welke cookies we plaatsen.</H5>
                    </div>
                    <div>
                        <H3>Over cookies</H3>
                        <P>Op onze website gebruiken wij cookies: kleine bestanden die verstuurd worden door een internetserver en die worden opgeslagen op je computer. Ook via derden die wij hebben ingeschakeld, worden cookies geplaatst.</P>
                    </div>
                    <div>
                        <H3>Beveiligingsmaatregelen</H3>
                        <P>Wanneer je onze website voor het eerst bezoekt, zie je onderaan het venster de mededeling "SURF plaatst functionele cookies om deze website goed te laten werken. Ook plaatsen we analytische cookies om de website te verbeteren. De data wordt waar mogelijk geanonimiseerd." Deze cookies plaatsen we zonder jouw uitdrukkelijke toestemming.</P>
                        <P>Daarnaast vragen we je toestemming voor het plaatsen van cookies van derden. Ingesloten content (zoals YouTube-video's) kan cookies van derde partijen bevatten. Door "Accepteer" te klikken, geef je toestemming voor het plaatsen van cookies van derden.</P>
                        <P>Je kunt de cookies weer verwijderen via je browserinstellingen. Daar kun je ook instellen welk soort cookies op je computer geplaatst worden. De meeste cookies hebben een houdbaarheidsdatum. Dat betekent dat ze na een bepaalde periode automatisch verlopen en geen gegevens van uw site-bezoek meer registeren. Je kunt er ook voor kiezen de cookies handmatig te verwijderen via je browserinstellingen.</P>
                    </div>
                    <div>
                        <H3>Afspraken met derden over cookies</H3>
                        <P>Met derden die cookies plaatsen hebben wij afspraken gemaakt over het gebruik van de cookies en applicaties. Toch heeft SURF geen volledige controle op wat de aanbieders van deze applicaties zelf met de cookies doen wanneer zij deze uitlezen. In de privacyverklaringen van deze partijen lees je meer over deze applicaties en hoe zij met cookies omgaan (let op: deze kunnen regelmatig wijzigen).</P>
                    </div>
                    <div>
                        <H3>Typen cookies op surf.nl</H3>
                        <H4>Functionele cookies</H4>
                        <P>Onze website gebruikt een aantal functionele cookies, die ervoor zorgen dat de website correct functioneert:</P>
                        <List>
                            <ListItem>cookieconsent_status: noodzakelijk om op te slaan wat je cookievoorkeur is en is 12 maanden geldig</ListItem>
                        </List>
                    </div>
                    <div>
                        <H3>Waarvoor gebruiken we je gegevens?</H3>
                        <Accordeon title={"SURF plaatst de volgende Matomocookies op je computer"}>
                            <div>
                                <AccordeonP>Als je je via onze website aanmeldt voor een evenement, vragen we de volgende persoonsgegevens:</AccordeonP>
                                <List>
                                    <ListItem>Voor- en achternaam (verplicht veld)</ListItem>
                                    <ListItem>E-mailadres (verplicht veld)</ListItem>
                                    <ListItem>Organisatie waar je werkt (verplicht veld)</ListItem>
                                    <ListItem>Organisatiesector waarin je werkt</ListItem>
                                    <ListItem>Functie</ListItem>
                                    <ListItem>Betalingsgegevens</ListItem>
                                    <ListItem>Dieetvoorkeuren</ListItem>
                                    <ListItem>Eventuele overige informatie die je vrijwillig invult</ListItem>
                                </List>
                            </div>
                        </Accordeon>
                    </div>
                    <div>
                        <H3>Wijziging cookieverklaring</H3>
                        <P>SURF behoudt zich het recht voor om wijzigingen aan te brengen in deze cookieverklaring. We raden je aan om deze cookieverklaring geregeld te raadplegen, zodat je van deze wijzigingen op de hoogte bent.</P>
                    </div>
                    <div>
                        <H3>Privacyverklaring</H3>
                        <P><TextA href="/cookies">Lees ook onze privacyverklaring.</TextA> Daar lees je hoe we omgaan met je persoonsgegevens, hoe wij jouw gegevens beveiligen en welke rechten jij hebt met betrekking tot jouw gegevens.</P>
                    </div>
                    <div>
                        <H3>Contactgegevens SURF</H3>
                        <ThemedH4>Algemeen</ThemedH4>
                        <Contact>
                            <P>SURF</P>
                            <P>Moreelsepark 48</P>
                            <P>3511 EP Utrecht</P>
                            <TextA href={"communicatie@surf.nl"}>communicatie@surf.nl</TextA>
                            <P>31 88 787 30 00</P>
                        </Contact>
                    </div>
                </PageContainer>
            }
        </div>
    )

}


const PageContainer = styled.div`
  margin: 0 20%;

  @media (max-width: 1024px) {
    margin: 0 10%;
  }

  @media (max-width: 768px) {
    margin: 0 5%;
  }
  & > div {
    margin-bottom: 20px;

    &:last-child {
      margin-bottom: 0;
    }
  }
`;
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


export default CookieStatement