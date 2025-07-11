
# 🌐 BCP47Tag
## 🪐 **Don’t panic. Your tag is valid.**
### Validate, Normalize & Canonicalize BCP 47 Language Tags. That would be `en`, `en-US`, etc ...

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-%3E=8.3-777bb4)
![Tests](https://github.com/lhcze/bcp47-tag/actions/workflows/php.yml/badge.svg)
![Packagist](https://img.shields.io/packagist/v/lhcze/bcp47-tag)
![IANA Registry](https://img.shields.io/badge/Source-IANA%20Language%20Subtag%20Registry-green)

**BCP47Tag** is a lightweight, robust PHP library for parsing, validating, normalizing, and resolving [BCP 47](https://tools.ietf.org/html/bcp47) language tags — the standard that powers `en-US`, `fr-CA`, `zh-Hant-CN`, `i-klingon` (🖖 Qapla’!), and more.

---

## ✅ **Why use BCP47Tag?**

- ✔️ **RFC 5646 / BCP 47 compliant** structure
- ✔️ Supports language, script, region, variant, grandfathered tags
- ✔️ Auto-normalizes casing & separators (`en_us` → `en-US`)
- ✔️ **Resolves partial language-only tags** (`en` → `en-US`) when you require a canonical tag
- ✔️ Validates against the **official IANA Language Subtag Registry**
- ✔️ Easy fallback mechanism
- ✔️ Zero hidden magic — clear, explicit resolution
- ️🫧 Supports grandfathered tags so old, they still remember when Unicode 2.0 was hot
- 🖖 Accepts `i-klingon` and `i-enochian` for your occult projects
- 🤓 `ABNF` so clean, linguists shed a single tear

---
## ❓ Why not just use `ext-intl`?

Good question — and the answer is: you **should** keep using it!   
`BCP47Tag` isn’t here to replace it — it exists to **make sure your language tags are clean, canonical, and safe *before* you hand them to ICU**.

Because we usually rely on **`ext-intl`** for date formats, currencies, or sorting rules — and it does that well, *if* the tag is valid.

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
echo $tag->getNormalized(); // en-US

// Use fallback if invalid
$tag = new BCP47Tag('notreal', fallbackLocale: 'fr-FR');
echo $tag->getNormalized(); // fr-FR

// Resolve partial to known canonical tag
$tag = new BCP47Tag(
    'en',
    knownTags: ['en-US', 'en-GB'],
    requireCanonical: true
);
echo $tag->getNormalized(); // en-US

// Feed to ext-intl
$icu = $tag->getICULocale(); // en_US
echo Locale::getDisplayLanguage($icu); // English

// Inspect parsed parts (language, script, region, variants)
$parsed = $tag->getParsedTag();
echo $parsed?->getLanguage(); // en
echo $parsed?->getRegion();   // US
```

---

## 🔍 **How Resolution Works**

✅ **`knownTags`**  
Provide an explicit list of canonical BCP 47 tags your app accepts.  
If the input is partial (like `en`), the tag will resolve to the first matching known tag (`en-US`). Position in the list is a priority.

✅ **`requireCanonical`**  
When true, language-only input must resolve to a canonical known tag — or the constructor will throw an `InvalidArgumentException`.

✅ **Fallback**  
If the input is invalid and a fallback is provided, it will be used instead.

---

## 🌐 **Powered by Official IANA Data**

BCP47Tag uses a **precompiled static PHP snapshot** of the latest **IANA Language Subtag Registry** to validate languages, scripts, regions, variants, and grandfathered tags.

The registry is loaded **once per process**, kept hot in OPcache for maximum speed.

---

## 🧩 **Key API**

| Method            | Purpose                                      |
|-------------------|----------------------------------------------|
| `getNormalized()` | RFC 5646 standard `xx-XX` format             |
| `getICULocale()`  | `xx_XX` format safe for `ext-intl`           |
| `getOriginalInput()` | Raw input string                          |
| `getParsedTag()` | Returns the ParsedTag value object for advanced inspection |
| `__toString()`    | Returns the normalized tag                   |

---

## 📜 The Official BCP 47 ABNF

The syntax your tags must follow is defined by [RFC 5646](https://datatracker.ietf.org/doc/html/rfc5646) in ABNF:

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

## ⚙️ **Requirements**

- PHP 8.3+
- `ext-intl`

---

## 🧪 **Tests**

```bash
vendor/bin/phpunit
```

---

## 📌 **Roadmap**

- ✅ Language, script, region, variant validation
- ✅ IANA subtag registry integration
- ✅ Canonical resolution with known tags
- ✅ Static PHP snapshot of the IANA registry for ultra-fast lookups
- ✅ Lazy singleton registry loader for low memory overhead
- ⚙️ Extensions & private-use subtags (planned)
- ⚙️ Automatic periodic IANA registry updates (planned)
- ⚙️ CLI tool to refresh the IANA data easily
- ⚙️ Optional Symfony service for container-based caching

---

## 📖 **License**

[MIT](LICENSE)

---

## 🔗 **References**

- [BCP 47 Specification (RFC 5646)](https://tools.ietf.org/html/rfc5646)
- [IANA Language Subtag Registry](https://www.iana.org/assignments/language-subtag-registry)

---

🧬 Now go and **boldly canonicalize strange new tags the BCP 47 way!** 🌍✨
