# Everywhere in this repository:
* **Always** Quote strings in YAML files.

# When editing files under `.github/workflows/` or `.github/actions/`:
* **Always** run `actionlint` against changed workflow files after editing them (e.g. `actionlint .github/workflows/<file>.yaml`) and resolve any reported issues before considering the change complete.
* If `actionlint` is not installed, download it (e.g. via the official install script at `https://raw.githubusercontent.com/rhysd/actionlint/main/scripts/download-actionlint.bash`) rather than skipping the check.
* Composite actions under `.github/actions/*/action.yaml` are not standalone workflows — `actionlint` will report spurious `"jobs"`/`"on"` schema errors if run directly against them. Lint them by way of a workflow that consumes them (e.g. running `actionlint` on the calling workflow file), not by targeting the `action.yaml` file itself.