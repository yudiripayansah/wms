#!/usr/bin/env python3
"""Apply PHP 8.4 compatibility fixes to filament vendor files."""
import sys

def fix_support():
    path = "vendor/filament/support/src/Concerns/ResolvesDynamicLivewireProperties.php"
    with open(path, "r") as f:
        content = f.read()
    if "method_exists($this, 'getId')" in content:
        print(f"[skip] support fix already present")
        return
    old = (
        "        } catch (PropertyNotFoundException $exception) {\n"
        "        }\n"
        "\n"
        "        if ("
    )
    new = (
        "        } catch (PropertyNotFoundException $exception) {\n"
        "        }\n"
        "\n"
        "        // PHP 8.4: guard against Livewire's built-in 'id' property\n"
        "        if ($property === 'id' && method_exists($this, 'getId')) {\n"
        "            return $this->getId();\n"
        "        }\n"
        "\n"
        "        if ("
    )
    if old not in content:
        print(f"[ERROR] support: expected context not found", file=sys.stderr)
        sys.exit(1)
    with open(path, "w") as f:
        f.write(content.replace(old, new, 1))
    print(f"[ok] support PHP 8.4 fix applied")

def fix_actions():
    path = "vendor/filament/actions/src/Concerns/InteractsWithActions.php"
    with open(path, "r") as f:
        content = f.read()
    if "is_object($action)" in content:
        print(f"[skip] actions fix already present")
        return
    old = '" . get_class($action) . \'].'
    new = '" . (is_object($action) ? get_class($action) : gettype($action)) . \'].'
    if old not in content:
        print(f"[ERROR] actions: expected context not found", file=sys.stderr)
        sys.exit(1)
    with open(path, "w") as f:
        f.write(content.replace(old, new, 1))
    print(f"[ok] actions PHP 8.4 fix applied")

fix_support()
fix_actions()
print("PHP 8.4 patches done.")
