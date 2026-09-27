# R9 cleanup report

## Deleted from the canonical branch

| File | Reason | Evidence |
| --- | --- | --- |
| `assets/css/main12.css` | Legacy cashier-only stylesheet | The V3 cashier loads `assets/css/checkout.css?v=3`; a recursive text search of the project found no runtime reference to `main12.css`. The file remains recoverable from Git history. |
| `assets/css/reset.css` | Legacy cashier reset stylesheet | The V3 cashier no longer loads it; recursive text search found no reference in PHP, HTML, CSS, JS or server configuration. The file remains recoverable from Git history. |

The temporary CSS fragment used while editing was created and removed in this branch; it was never a production resource.

## Keep

- `assets/css/checkout.css`: the single canonical payment frontend stylesheet, referenced by the cashier and 38 other PHP payment views/helpers.
- `assets/js/checkout.js`: cashier form enhancement with parameter encoding and repeat-submit protection.
- `assets/icon/*.ico` and `assets/pay/icon/*`: payment method and other payment page assets; no bulk deletion was performed.
- `assets/files/SDK.zip` and `assets/files/SDK_2.0.zip`: public SDK archives. Their download links and external consumers were not fully proven absent.
- Inner Git history, migrations, runtime configuration and third-party dependencies.

## Review and defer

- Outer `.git-disabled-20260927`, the outer source tree and `3141cafcd7ae4fca951c5bf6cc62aec2.zip` are outside the canonical repository. The outer `README.md` is already missing from that tree. They contain recoverable history and have not been deleted. Archive/cleanup requires completed remote verification and confirmation that no remaining source or configuration is unique.
- Other differing files in `R9_DIFF_INVENTORY.md` remain REVIEW unless a specific reference and runtime-dependency check proves deletion safe.
