#!/usr/bin/env bash
# Run from the repository root against the disposable orb fixture only.
set -euo pipefail
export AGENT_BROWSER_NAMESPACE="recent-test-$$"
url=http://localhost:8765/panel/site
box='.k-field-name-modifier .k-box'
note='.k-field-name-note input'
run=$(date +%s%N)
b() { agent-browser --session "$editor" "$@"; }
button() { b find role button click --exact --name "$1"; }
cleanup() { for editor in Robin Sam; do b close >/dev/null 2>&1 || :; done; }
trap cleanup EXIT
trap 'echo "Failed at line $LINENO ($editor)" >&2; b get url; b snapshot | tail -40' ERR

# CLI, loopback and portal share this fixture's plugin cache. Start with no history.
php -r '(require "tests/app.php")->cache("bnomei.recently-modified")->flush() || exit(1);'
for editor in Robin Sam; do
  b open "$url"
  b set viewport 1100 750 2
  b wait 'input[type=password]'
  b fill 'input[type=email]' "${editor,,}@example.test"
  b fill 'input[type=password]' "$(cat tests/.password)"
  button 'Log in'
  b wait --fn "document.querySelector('.k-field-name-recent')?.innerText.includes('No pages yet')"
  b wait --fn "document.querySelectorAll('.k-field-name-catalog a').length === 2"

  slug=weekend-guide; [[ $editor == Sam ]] && slug=field-notes
  b click ".k-field-name-catalog a[href$='/$slug']"
  b wait --url "**/pages/$slug"
  b wait --text 'Last page edit'
  b fill "$note" "$editor's edit $run"
  b wait "$box[data-theme=notice]"
  button Save
  b wait --fn "document.querySelector('$box[data-theme=info]')?.innerText.startsWith('$editor, ')"
  b fill "$note" 'Discard this change'
  button Discard
  button 'Discard changes'
  b wait --fn "document.querySelector('$note')?.value.endsWith('$run') && !!document.querySelector('$box[data-theme=info]')"
  b find role link click --name Preview --exact
  b wait --url "**/$slug/preview/form"
  b click '.k-preview-form .k-field-name-related a'
  other=field-notes; [[ $editor == Sam ]] && other=weekend-guide
  b wait --url "**/$other/preview/form"
  b open "$url"
done

# Robin keeps the site open while Sam saves. Only the polling field should change.
editor=Robin
b fill "$note" "Robin's site edit $run"
button Save
b wait --fn "document.querySelector('$box[data-theme=info]')?.innerText.startsWith('Robin, ')"
editor=Sam
b reload
b wait --fn "document.querySelector('$box')?.innerText.startsWith('Robin, ')"
b fill "$note" "Sam's site edit $run"
button Save
b wait --fn "document.querySelector('$box[data-theme=info]')?.innerText.startsWith('Sam, ')"
editor=Robin
b wait --fn "document.querySelector('$box[data-theme=negative]')?.innerText.startsWith('Sam, ')"

# A list query can stay warm for the documented one-minute TTL.
for editor in Robin Sam; do
  slug=weekend-guide; [[ $editor == Sam ]] && slug=field-notes
  for attempt in {1..14}; do
    b reload
    b wait '.k-field-name-recent'
    if [[ $(b get count ".k-field-name-recent a[href$='/$slug']") == 1 ]]; then break; fi
    sleep 5
  done
  [[ $(b get count ".k-field-name-recent a[href$='/$slug']") == 1 ]]
  [[ $(b get count '.k-field-name-recent a') == 1 ]]
  [[ -z $(b errors) ]]
done
echo 'PASS: save/discard, preview navigation, cross-editor refresh, and per-user lists'
