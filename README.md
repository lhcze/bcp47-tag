# 🌐 BCP47Tag
## 🪐 **Don’t panic. Your tag is valid.**
### Validate, Normalize & Canonicalize BCP 47 Language Tags (`en`, `en-US`, `zh-Hant-CN`, etc.)

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-%3E=8.3-777bb4)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/lhcze/bcp47-tag/php.yml)
![Packagist](https://img.shields.io/packagist/v/lhcze/bcp47-tag)
![Downloads](https://img.shields.io/packagist/dt/lhcze/bcp47-tag)
![IANA Registry](https://img.shields.io/badge/Source-IANA%20Language%20Subtag%20Registry-green)

**BCP47Tag** is a robust PHP library for working with BCP 47 language tags:

- ✔️ Validates against the real IANA Language Subtag Registry
- ✔️ ABNF-compliant (RFC 5646)
- ✔️ Supports language, script, region, variant, grandfathered tags
- ✔️ Auto-normalizes casing & separators (`en_us` → `en-US`)
- ✔️ Automatically expands collapsed ranges from the registry
- ✔️ Resolves partial language tags (e.g., `en` → `en-US`) using custom canonical matching, with scoring
- ✔️ Error handling via clear exception types
- ✔️ Lightweight `LanguageTag` VO for validated tags
- ✔️ Works perfectly with `ext-intl`—no surprises upon feeding ICU
- ✔️ Easy fallback mechanism
- ️🫧 Supports grandfathered tags so old, they still remember when Unicode 2.0 was hot
- 🖖 Accepts `i-klingon` and `i-enochian` for your occult projects
- 🤓 `ABNF` so clean, linguists shed a single tear
---
## ❓ Why not just use `ext-intl`?
Good question — and the answer is: you **should** keep using it!
`ext-intl` (ICU) is brilliant at formatting *if* your tag is clean.  

However, it does **not**:

- ✅ Validate that your tag fully follows the **BCP 47 ABNF** rules.
- ✅ Reject or warn about **grandfathered** or **deprecated subtags**.
- ✅ Match your tags against the authoritative **IANA Language Subtag Registry**.
- ✅ **Resolve partial input** (`en` → `en-US`) to a known canonical list.
- ✅ Enforce **known tags only** with `knownTags` + `requireCanonical`.

> If you’re in Symfony, you might also use `#[Assert\Locale]` for basic input validation.  
And that’s fine for checking user input — but it stops at *structure*. It won’t canonicalize, resolve, or check IANA.

👉 **So the best practice:**
- ✅ Use **BCP47Tag** to *validate & normalize*.
- ✅ Hand the cleaned tag to `ext-intl` or whatever else you have for formatting & display.
- ✅ Trust you’ll never feed ICU any garbage.
- ✅ Carry around immutable LanguageTag value object across your code base instead of string

**BCP47Tag**: RFC 5646 + IANA + real normalization + fallback + resolution.  
No hustle with regex, `str_replace()` or guesswork.

---

## ⚡️ **Installation**

```bash
composer require lhcze/bcp47-tag
```

---

## 🚀 **Basic Usage**

```php
use LHcze\BCP47\BCP47Tag;

// Just normalize & validate
$tag = new BCP47Tag('en_us');
echo $tag->getNormalized();    // "en-US"
echo $tag->getICUformat();   // "en_US"

// With canonical matching
$tag = new BCP47Tag('en', useCanonicalMatchTags: ['de-DE', 'en-US']);
echo $tag->getNormalized();    // "en-US"

// Use fallback if invalid
$tag = new BCP47Tag('notreal', 'fr-FR');
echo $tag->getNormalized(); // fr-FR

// Invalid input → exception
try {
    new BCP47Tag('invalid!!');
} catch (BCP47InvalidLocaleException $e) {
    echo $e->getMessage();
}

// Feed to ext-intl
$icu = $tag->getICULocale(); // en_US
echo Locale::getDisplayLanguage($icu); // English

// LanguageTag VO
$langTag = $tag->getLanguageTag();
echo $langTag->getLanguage();  // "en"
echo $langTag->getRegion();    // "US"
echo (string) $langTag;        // "en-US"
```

---

## 🔍 **Features & Flow**

1. **Normalize + parse**  
   Clean casing/formatting and parse into components.

2. **Validate against IANA**  
   Broken input or fallback triggers explicit exceptions:
    - `BCP47InvalidLocaleException`
    - `BCP47InvalidFallbackLocaleException`

3. **Canonical matching (optional)**
    - Pass an array of `useCanonicalMatchTags`
    - Each is matched and scored:  
      +100 language match, +10 region, +1 script
    - Highest score wins.
    - Same score makes the first one to have it to make a home run

4. **LanguageTag VO**  
   Immutable, validated, `Stringable` & `JsonSerializable`.

---

## 📜 Supported Tags
BCP47Tag uses a **precompiled static PHP snapshot** of the latest **IANA Language Subtag Registry** to validate languages, scripts, regions, variants, and grandfathered tags.
The registry is loaded **once per process**, kept hot in OPcache for maximum speed.
- ✅ ISO language, script, region, variants
- ✅ Grandfathered/deprecated tags (e.g., `i-klingon`)
- ✅ Collapsed registry ranges are auto-expanded
- ⚠️ Extensions & private-use subtags (future)

---

## 🧩 **Key API**

| Method | Description |
|--------|-------------|
| `__construct(string $input, ?string $fallback, ?array $useCanonicalMatchTags)` | Main entry |
| `getInputLocale()` | Original input string |
| `getNormalized()` | RFC‑5646 formatted tag |
| `getICUformat()` | Underscore variant (`xx_XX`) |
| `getLanguageTag()` | Returns `LanguageTag` VO |
| `__toString()` / `jsonSerialize()` | Returns normalized string |

---

## 📜 The Official BCP 47 ABNF

The syntax tags must follow is defined by [RFC 5646](https://datatracker.ietf.org/doc/html/rfc5646) in ABNF:

```abnf
langtag = language
   ["-" script]
   ["-" region]
   *("-" variant)
   *("-" extension)
   ["-" privateuse]
```

Examples:
- ✅ `en` → valid
- ✅ `en-US` → valid
- ✅ `zh-Hant-CN` → valid
- ✅ `i-klingon` → valid (grandfathered)
- ✅ `en-US-x-private` → valid (extension/private use)
- ❌ `en-US--US` → invalid

BCP47Tag respects this ABNF, so your tags match the real spec — no hidden assumptions.

---
## ❓ **Why is this useful?**

Use cases include:
- Validating API `Accept-Language` headers
- Multi-regional CMS deployments
- Internationalization pipelines
- Locale-dependent services where mis-typed tags lead to silent failures

---

## ⚙️ **Requirements**

- PHP 8.3+
- `ext-intl`

---

## 🧪 **Tests**

```bash
composer qa
```

---

## 📌 **Roadmap**

- ✅ IANA Language Subtag Registry integration
- ✅ Language, script, region, variant validation
- ✅ Lazy singleton registry loader
- ✅ Static PHP snapshot of the IANA registry for ultra-fast lookups
- ✅ Canonical matching with scoring
- ✅ Typed exceptions for flow control
- ⚙️ Extension/subtag support (planned)
- ⚙️ Additional data use from IANA registry (suppress-script subtag, preferred, prefix)
- ⚙️ Auto-registry refresh script

---


## 📖 License

[MIT](LICENSE)

---

## 🔗 References

- [RFC 5646 – BCP 47 ABNF](https://tools.ietf.org/html/rfc5646)
- [IANA Language Subtag Registry](https://www.iana.org/assignments/language-subtag-registry)

---

🧬 Now go and **boldly canonicalize strange new tags the BCP 47 way!** 🌍✨
