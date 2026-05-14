# Tally/Zapier Teilnehmer-Entwürfe

Das Plugin stellt für Tally-Formulare über Zapier einen REST-Endpunkt bereit, der noch keine finale Teilnehmerbuchung erstellt. Eingehende Datensätze werden als Teilnehmer-Entwürfe gespeichert und können danach im WordPress-Admin unter **Teilnehmer-Entwürfe** geprüft, bearbeitet, gelöscht und manuell einer Reise-ID zugewiesen werden.

## Plugin-Einstellungen

Öffne im WordPress-Admin **Teilnehmer-Entwürfe → Einstellungen** und speichere:

- **Zapier API-Key**: geheimer Wert, den Zapier im Header `X-API-Key` sendet.
- **Standard-Status für neue Teilnehmer-Entwürfe**: Standard ist `draft`.
- **Standard-Quelle für Zapier**: Standard ist `tally_zapier`.

## Zapier Webhook-Konfiguration

- Methode: `POST`
- URL: `https://MEINE-DOMAIN.at/wp-json/mein-plugin/v1/participant-drafts`
- Header: `X-API-Key: DER_API_KEY_AUS_DEN_PLUGIN_EINSTELLUNGEN`
- Payload Type: `JSON`
- Content-Type: `application/json`

## Tally-Feldmapping für Zapier

| Tally-Feld | JSON-Feld |
| --- | --- |
| Vorname | `first_name` |
| Nachname | `last_name` |
| E-Mail | `email` |
| Telefonnummer | `phone` |
| Geburtsdatum | `birthdate` |
| Geschlecht | `gender` |
| Adresse | `address` |
| Stadt | `city` |
| PLZ | `zip` |
| Land | `country` |
| Gewünschte Reise | `desired_trip` |
| Programm | `program` |
| Notizen | `notes` |

Pflichtfelder sind `first_name`, `last_name` und `email`. Bei gleicher E-Mail-Adresse und gleicher gewünschter Reise oder gleichem Programm aktualisiert das Plugin den bestehenden Entwurf statt einen neuen Datensatz anzulegen.
