Süper! İşte GitHub uyumlu, sade ve şık bir `README.md` dosyası:

---

```markdown
# Zeus\Pipe\Filter

A simple, chainable filter class using closures.

## 🚀 Example

```php
use Zeus\Pipe\Filter;

$filter = new Filter();

$filter->add(fn($value) => is_int($value));      // Must be an integer
$filter->add(fn($value) => $value > 10);         // Greater than 10
$filter->add(fn($value) => $value % 2 === 0);    // Must be even

$data = [5, 12, 15, 20, '30', 25];
$result = $filter->apply($data);

print_r($result);
// Output: [1 => 12, 3 => 20]
```

## ✅ Description

- Add multiple filter conditions using closures.
- All conditions are combined with logical AND (`&&`).
- `apply()` returns only the values that pass **all** filters.

## 📦 Requirements

- PHP 8.0 or higher

## 📝 License

MIT

```

---

İstersen `composer.json` örneği ya da `namespace` yapısına uygun klasör düzeni de ekleyebilirim. Yardımcı olayım mı?