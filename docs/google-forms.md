# Setting up the Google Forms (Phase 0)

The site links to three Google Forms. Each answer lands in a Google Sheet, and
the site fills in hidden questions so every enquiry records where it came from
(MKT-3). Until a form's URL is set in `.env`, its buttons open WhatsApp instead.

| Form | `.env` URL | Used by |
|---|---|---|
| Tuition enquiry and free demo | `FORM_URL_TUITION` | Tuition page, tuition guides, contact page |
| Academic enquiry and quote | `FORM_URL_ACADEMIC` | Academic page, academic guides, contact page |
| Waitlist ("Notify me") | `FORM_URL_WAITLIST` | Waitlist boxes on both sections |

## 1. Create each form

In Google Forms, create the form and add your visible questions. Suggested:

- **Tuition:** parent's name, mobile (WhatsApp), email, child's class, board,
  subjects, "Which monthly fee per subject suits you?" (three ranges), "How did
  you hear about us?", and a consent tick for marketing messages (optional).
  Do not ask for the child's surname, phone or photo.
- **Academic:** name, mobile, email, level (Bachelors / Masters / PhD),
  university, service, page or word count, deadline, city (for printing),
  "Which price range suits you?", "How did you hear about us?", and a
  required tick "I am 18 or older".
- **Waitlist:** name, mobile or email, city, and what they want to hear about.

Then add five **short-answer** questions at the end of each form, titled
`Source`, `Medium`, `Campaign`, `Page` and `Interest`. Put them in their own
last section titled "For office use, please leave as is". The site fills them in.

In the Responses tab, click **Link to Sheets** so answers go to a Google Sheet.

## 2. Copy the form link

Click **Send**, choose the link tab, and copy the link. It looks like
`https://docs.google.com/forms/d/e/1FAIpQL.../viewform`. Paste it into `.env`:

```
FORM_URL_TUITION=https://docs.google.com/forms/d/e/1FAIpQL.../viewform
```

## 3. Find the entry ids

Open the form's ⋮ menu, choose **Get pre-filled link**, type `x` into each of
the five hidden questions, and click **Get link**, then **Copy link**. The copied
link contains parts like `entry.123456789=x`, one per question, in form order.
Put each id into `.env`, for example:

```
FORM_TUITION_ENTRY_SOURCE=entry.123456789
FORM_TUITION_ENTRY_MEDIUM=entry.234567890
FORM_TUITION_ENTRY_CAMPAIGN=entry.345678901
FORM_TUITION_ENTRY_PAGE=entry.456789012
FORM_TUITION_ENTRY_INTEREST=entry.567890123
```

Repeat with `FORM_ACADEMIC_ENTRY_*` and `FORM_WAITLIST_ENTRY_*` for the other forms.

## 4. Test it

1. Restart `composer run dev` so it reads the new `.env`.
2. Open `http://localhost:8000/academic?utm_source=test&utm_medium=whatsapp&utm_campaign=trial`.
3. Click **Get a price for your work**. The hidden questions show `test`,
   `whatsapp`, `trial`, `academic` and `General enquiry`. Submit it.
4. Check the Google Sheet: the row carries the source. That is M1's "done when".

## Tagging the links you share

Add UTM tags to every link you post, so the Sheet shows which channel works:

```
https://yourdomain.in/academic?utm_source=whatsapp&utm_medium=status&utm_campaign=phd-synopsis-oct
https://yourdomain.in/tuition?utm_source=flyer&utm_medium=qr&utm_campaign=society-a-oct
```

The site remembers the tags while the visitor browses, so an enquiry from a
later page still records them. A visit with no tags records the referring
website, or `direct`.
