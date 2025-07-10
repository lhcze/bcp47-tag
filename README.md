
# 🌐 BCP47Tag
## 🪐 **Don’t panic. Your tag is valid.**
### Validate, Normalize & Canonicalize BCP 47 Language Tags. That would be `en`, `en-US`, etc ...

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-%3E=8.3-777bb4)
![Tests](https://img.shields.io/badge/tests-passing-brightgreen)

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
## ❓ Why not just use `ext-intl` or `#[Assert\Locale]`?

Well, good question!
Most PHP developers reach for **`ext-intl`**, **`symfony/intl`**, or a **`#[Assert\Locale]`** constraint — and these work great for **basic locale lookups**.

However, they do **not**:

- ✅ Validate the full **BCP 47 ABNF** structure
- ✅ Respect **grandfathered** or **deprecated subtags**
- ✅ Match your tags against the **IANA Language Subtag Registry**
- ✅ Help you **resolve partial tags** (`en` → `en-US`) with your own canonical list. (yeas!)
- ✅ Enforce **known tags only** rules — `knownTags` + `requireCanonical`

**BCP47Tag** fills this gap:  
-> RFC 5646 + IANA + real normalization + fallback + resolution.

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

// Get format variants
echo $tag->getLCU(); // en_US
echo $tag->getUCU(); // EN_US
echo $tag->getLC();  // en-us
echo $tag->getUC();  // EN-US
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

BCP47Tag uses the latest **IANA Language Subtag Registry** to validate languages, scripts, regions, variants, and grandfathered tags.

---

## 🧩 **Key API**

| Method            | Purpose                                      |
|-------------------|----------------------------------------------|
| `getNormalized()` | RFC 5646 standard `xx-XX` format             |
| `getUnderscored()`| `xx_XX` format for systems that use underscores |
| `getLC()`         | Lowercase tag (`xx-xx`)                      |
| `getUC()`         | Uppercase tag (`XX-XX`)                      |
| `getLCU()`        | Lowercase, underscored (`xx_xx`)             |
| `getUCU()`        | Uppercase, underscored (`XX_XX`)             |
| `getOriginalInput()` | Raw input string                          |
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
- `symfony/intl`

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
- ⚙️ Extensions & private-use subtags (planned)
- ⚙️ Automatic periodic IANA registry updates (planned)
- ⚙️ Symfony Bundle auto-wiring (optional)

---


## 📖 **License**

[MIT](LICENSE)

---

## 🔗 **References**

- [BCP 47 Specification (RFC 5646)](https://tools.ietf.org/html/rfc5646)
- [IANA Language Subtag Registry](https://www.iana.org/assignments/language-subtag-registry)
- [Symfony Intl](https://symfony.com/doc/current/components/intl.html)

---

🧬 Now go and **boldly canonicalize strange new tags the BCP 47 way!** 🌍✨