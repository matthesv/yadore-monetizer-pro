# Yadore Publisher API – Technische Dokumentation (v2.0)

Diese Datei beschreibt die verfügbaren Endpunkte der **Yadore Publisher API** sowie Parameter, Antwortstrukturen und Beispiel‑Requests.  
Alle Endpunkte liegen unter der Basis‑URL: `https://api.yadore.com/`

---

## Authentifizierung

- Verwende den HTTP‑Header `API-Key: <DEIN_API_KEY>` für **alle** Requests.
- Werte und Parameter sind **case sensitive**.
- Alle **Datumsangaben** in Responses sind in **UTC**.
- Alle **Beträge** sind in **Euro (EUR)**.
- Publisher‑Klassifizierung: `no couponing`, `couponing`, `mixed`.
  - Wenn dein Projekt als **mixed** klassifiziert ist, **musst** du den Query‑Parameter `isCouponing` setzen (siehe Endpunkte).

---

## Allgemeine Hinweise

- `precision` (bei Volltextsuche): `strict` (strenger, relevanter) oder `fuzzy` (lockerer, mehr Treffer). Standard: `fuzzy`.
- `sort`: `rel_desc`, `price_asc`, `price_desc`. Standard: `rel_desc`. **Relevanz‑Sortierung funktioniert nur mit gesetztem `keyword`.**
- `limit`: 1–100. Standard: 20.
- **Deeplink-/Click‑URLs** sind nur **14 Tage** gültig.
- Nutze `placementId` als eigene SubID zur Zuordnung deiner Klicks (max. 128 ASCII‑Zeichen).

---

## 1) Offer – Produktangebote

### GET `/v2/offer` – Angebote suchen
**Beschreibung:** Suche Produktangebote, optional gefiltert und sortiert.

**Query‑Parameter**
- `market` *(string, required)* – Markt, für den du freigeschaltet bist (z. B. `de`).  
- `keyword` *(string)* – Suchbegriff (mind. 1 Zeichen).
- `ean` *(string)* – 8 oder 13 Zeichen.
- `merchantId` *(string)* – Filter auf Händler.
- `offerId` *(string)* – Liefert nur das eine Angebot (falls aktiv).
- `placementId` *(string)* – Eigene SubID.
- `precision` *(string)* – `strict` | `fuzzy` (default).
- `sort` *(string)* – `rel_desc` (default) | `price_asc` | `price_desc`.
- `limit` *(integer)* – 1–100 (default 20).
- `isCouponing` *(boolean)* – **Pflicht**, wenn Projekt als `mixed` klassifiziert.

**Response 200 – `OfferResponse`**
```json
{
  "count": 0,
  "total": 0,
  "offers": [
    {
      "id": "string",
      "title": "string",
      "description": "string",
      "promoText": "string|null",
      "brand": "string|null",
      "clickUrl": "string",
      "availability": "AVAILABLE|UNAVAILABLE|UNKNOWN|SOON|PREORDER|AVAILABLE ON ORDER|STOCK ON ORDER|BACKORDER",
      "eer": "string|null",
      "shippingTime": { "text": "string" },
      "merchant": {
        "id": "string",
        "name": "string",
        "logo": { "url": "string", "exists": true }
      },
      "price": { "amount": "string", "currency": "EUR" },
      "originalPrice": { "amount": "string", "currency": "EUR" },
      "shippingPrice": { "amount": "string", "currency": "EUR" },
      "estimatedCpc": { "amount": "string", "currency": "EUR" },
      "unitPrice": { "text": "string|null" },
      "image": { "url": "string" },
      "thumbnail": { "url": "string" }
    }
  ]
}
```

**Beispiel (curl)**
```bash
curl -G "https://api.yadore.com/v2/offer" \
  -H "API-Key: $API_KEY" \
  --data-urlencode "market=de" \
  --data-urlencode "keyword=Waschmaschine" \
  --data-urlencode "precision=strict" \
  --data-urlencode "sort=price_asc" \
  --data-urlencode "limit=20"
```

**Fehler 400 (Beispiel)**
```json
{
  "errors": {
    "isCouponing": [
      "Field is required when mixed traffic is allowed",
      "Field must be a boolean"
    ],
    "market": [
      "Field is required",
      "Market 'xy' not found"
    ]
  }
}
```

---

### GET `/v2/offer/bulk` – Angebote per EAN‑Liste
Suche Angebote für mehrere EANs (je EAN max. 50 Treffer).

**Query‑Parameter**
- `market` *(required)*
- `eans` *(required)* – Kommagetrennte Liste mit 8/13‑stelligen EANs (max. 50).
- `merchantId`, `placementId`, `isCouponing` *(optional)*

**Response 200 – `OfferEanBulkResponse`**
```json
{
  "ean": {
    "count": 0,
    "offers": [ /* Offer-Objekte wie oben */ ]
  }
}
```

**Beispiel (curl)**
```bash
curl -G "https://api.yadore.com/v2/offer/bulk" \
  -H "API-Key: $API_KEY" \
  --data-urlencode "market=de" \
  --data-urlencode "eans=4002515827847,8806098765432" \
  --data-urlencode "limit=50"
```

---

### GET `/v2/merchant` – Aktive Händler
**Query‑Parameter**
- `market` *(required)*
- `isCouponing` *(boolean, optional)* – Filter für mixed‑Projekte.

**Beispiel (curl)**
```bash
curl -G "https://api.yadore.com/v2/merchant" \
  -H "API-Key: $API_KEY" \
  --data-urlencode "market=de"
```

---

## 2) Deeplink & Smartlink

### POST `/v2/deeplink` – Click‑URLs erzeugen
Erzeuge monetarisierte Click‑URLs für konkrete Landingpages (Deeplinks) oder Smartlink‑Händler. **Bis zu 20 URLs pro Request.**

**Request‑Body `application/json`**
```json
{
  "market": "de",
  "placementId": "abc-123",
  "isCouponing": false,
  "urls": [
    { "url": "https://shop.example.de/category/product123.html" },
    { "url": "https://shop.example.de/" }
  ]
}
```

**Response 200 – `DeeplinkResponse` (Auszug)**
```json
{
  "result": {
    "found": 1,
    "total": 2,
    "deeplinks": [
      {
        "url": "https://shop.example.de/category/product123.html",
        "found": true,
        "clickUrl": "https://clk.yadore.com/....",  // 14 Tage gültig
        "merchant": {
          "logo": { "url": "https://...", "exists": true }
        }
      }
    ]
  }
}
```

**Beispiel (curl)**
```bash
curl "https://api.yadore.com/v2/deeplink" \
  -H "API-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
        "market":"de",
        "placementId":"my-subid",
        "urls":[{"url":"https://shop.example.de/product/123"}]
      }'
```

---

### GET `/v2/deeplink/merchant` – Deeplink/Smartlink‑Händler
**Query‑Parameter**
- `market` *(required)*
- `isSmartlink` *(optional, „1“ für nur Smartlink‑Händler)*
- `hasHomepage` *(optional, „1“ für nur Händler mit Homepage)*
- `isCouponing` *(optional)*

**Beispiel (curl)**
```bash
curl -G "https://api.yadore.com/v2/deeplink/merchant" \
  -H "API-Key: $API_KEY" \
  --data-urlencode "market=de" \
  --data-urlencode "isSmartlink=1"
```

---

## 3) Direct Redirect

### GET `/v2/d` – Redirect auf Ziel‑URL
Leite Nutzer:innen auf eine Ziel‑URL um; Yadore erzeugt die monetarisierte Tracking‑URL.

**Query‑Parameter**
- `url` *(required)* – exakte Shop‑URL.
- `market` *(required)*
- `projectId` *(required)* – erfrage ihn bei deinem Account‑Manager.
- `merchantId`, `placementId`, `callbackUrl`, `isCouponing` *(optional)*  
  `callbackUrl` muss **vorher whitelisted** sein; sonst 404 bei Fehlerfällen.

**Antwort**
- `302` Redirect auf die Zielseite (bzw. monetarisierte URL).
- `404` wenn kein Match & kein `callbackUrl`.

**Beispiel (curl)**
```bash
curl -i -G "https://api.yadore.com/v2/d" \
  -H "API-Key: $API_KEY" \
  --data-urlencode "url=https://shop.example.de/product/123" \
  --data-urlencode "market=de" \
  --data-urlencode "projectId=YOUR_PROJECT_ID" \
  --data-urlencode "placementId=my-subid"
```

---

## 4) Reporting

### GET `/v2/report/status` – Tagesverfügbarkeit
Zeigt, ob die Reports für ein Datum **vollständig** sind (Änderungen/Nachläufer ausgenommen).

**Query‑Parameter**
- `date` *(required, `YYYY-MM-DD`)*

**Beispiel (curl)**
```bash
curl -G "https://api.yadore.com/v2/report/status" \
  -H "API-Key: $API_KEY" \
  --data-urlencode "date=2025-10-09"
```

---

### GET `/v2/report/general` – Tagesübersicht pro Markt
Aggregierte Klicks/Umsatz pro Markt.

**Query‑Parameter**
- `date` *(required, `YYYY-MM-DD`)*
- `format` *(required, `json|csv`)*

**Response 200 – `ReportGeneralResponse` (Auszug)**
```json
{
  "date": { "from": "2025-10-09", "to": "2025-10-09" },
  "total": { "clicks": 0, "revenue": 0, "currency": "EUR" },
  "market": {
    "de": {
      "total": { "clicks": 0, "revenue": 0, "currency": "EUR" }
    }
  }
}
```

---

### GET `/v2/report/detail` – Klick‑Einzelreport (Tag)
Detaillierte Klick‑Events für einen Tag.

**Query‑Parameter**
- `date` *(required, `YYYY-MM-DD`)*
- `format` *(required, `json|csv`)*
- `market` *(optional)*

**Response 200 – `ReportDetailResponse` (Auszug)**
```json
{
  "totalClicks": 0,
  "clicks": [
    {
      "clickId": "string",
      "date": "2025-10-09T12:34:56Z",
      "placementId": "string",
      "market": "de",
      "merchant": { "id": "string", "name": "string" },
      "revenue": 0,
      "currency": "EUR"
    }
  ]
}
```

---

### GET `/v2/report/modified` – Änderungsdaten (tagesbasiert)
Liefert die Tage, an denen Reports zuletzt geändert wurden (UTC).

**Query‑Parameter**
- `from` *(required, `YYYY-MM-DD`)*
- `to` *(required, `YYYY-MM-DD`)*
- `market` *(optional, ISO‑3166‑Alpha‑2)*

**Response 200 – `ReportModifiedResponse` (Auszug)**
```json
{
  "market": {
    "date": "2025-10-01",
    "modifiedDate": "2025-10-02T07:30:00Z"
  }
}
```

---

## 5) Conversion‑Reporting

### GET `/v2/conversion/status` – Tagesverfügbarkeit
Wie Report‑Status, nur für Conversions (späte Meldungen üblich).

**Query‑Parameter**
- `date` *(required, `YYYY-MM-DD`)*

---

### GET `/v2/conversion/general` – Zeitraum, gruppiert
Aggregierte Klicks/Verkäufe pro Markt für Zeitraum.

**Query‑Parameter**
- `from` *(required, `YYYY-MM-DD`)*
- `to` *(required, `YYYY-MM-DD`)*
- `format` *(required, `json|csv`)*

---

### GET `/v2/conversion/detail` – Klick‑Einzelreport (Tag)
Wie `report/detail`, aber nur **trackbare** (vergütete) Klicks.

**Query‑Parameter**
- `date` *(required, `YYYY-MM-DD`)*
- `format` *(required, `json|csv`)*
- `market` *(optional)*

---

### GET `/v2/conversion/detail/merchant` – Qualität nach Händler
Zeitraumbezogene Übersicht, gruppiert nach Händler.

**Query‑Parameter**
- `market` *(optional, ISO‑3166‑Alpha‑2)*
- `from` *(required, `YYYY-MM-DD`)*
- `to` *(required, `YYYY-MM-DD`)*
- `format` *(required, `json|csv`)*

---

## 6) Märkte

### GET `/v2/markets` – Freigegebene Märkte
Liefert die Liste der Märkte, in die du Traffic senden darfst.

**Beispiel (curl)**
```bash
curl -G "https://api.yadore.com/v2/markets" \
  -H "API-Key: $API_KEY"
```

---

## Typen & Schemata (Auszug)

### `Offer`
- `id`, `title`, `description`, `promoText?`, `brand?`
- `clickUrl`
- `availability` *(Enum, siehe oben)*
- `eer?`
- `shippingTime.text`
- `merchant.id`, `merchant.name`, `merchant.logo.url`, `merchant.logo.exists`
- `price.amount`, `price.currency`
- `originalPrice.amount`, `originalPrice.currency`
- `shippingPrice.amount`, `shippingPrice.currency`
- `estimatedCpc.amount`, `estimatedCpc.currency` *(Brutto‑CPC von Yadore; zur eigenen Revenue‑Schätzung Anteil anwenden)*
- `unitPrice.text?`
- `image.url`, `thumbnail.url`

### `OfferResponse`
- `count`, `total`, `offers: Offer[]`

### `DeeplinkResponse`
- `result.found`, `result.total`, `result.deeplinks[]` mit `url`, `found`, `clickUrl`, optional `merchant.logo`

### Reporting/Conversion – Auszüge
- `ReportStatusResponse`: `status` = `complete|incomplete`
- `ReportGeneralResponse`: `date{from,to}`, `total{clicks,revenue,currency}`, `market{<code>.total{...}}`
- `ReportDetailResponse`: `totalClicks`, `clicks[]` (siehe oben)
- `ReportModifiedResponse`: `market.date`, `market.modifiedDate`
- `ConversionStatusResponse`: `status` wie oben

---

## Best Practices

- **Vor Reports** immer `report/status` prüfen (Datenvollständigkeit; Nachmeldungen).
- Für Conversion‑Reports Zeitraum mind. **2 Tage zurück** starten (späte Verkäufe).
- Relevanzsortierung nur mit `keyword` nutzen; sonst ist die Reihenfolge **undefiniert**.
- `placementId` konsequent setzen für klare Zuordnung & spätere Auswertungen.
- `callbackUrl` für Redirects **vorher whitelisten** lassen.
- Bei `mixed`‑Traffic **immer** `isCouponing` setzen.

---

## Beispiel: Minimaler Angebotsabruf

```bash
API_KEY="***dein key***"

curl -G "https://api.yadore.com/v2/offer" \
  -H "API-Key: $API_KEY" \
  --data-urlencode "market=de" \
  --data-urlencode "limit=10"
```

> Hinweis: Ersetze `$API_KEY` durch deinen echten Schlüssel. Für sensible Nutzung den Key nicht in Logs/Repos speichern.