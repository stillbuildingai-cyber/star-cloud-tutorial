# Workflow Commands

統一入口：`./workflow`

## 查看可用指令

```bash
./workflow list
```

## 執行指令

```bash
./workflow run <name> [args...]
```

## 指令對照

- `load-ids`
  - 載入 `~/.gemini/AGENTS.md` 與 `~/.gemini/GEMINI.md` 內容。

- `daily-work-log`
  - 輸出今日 commit：`git log --since="midnight" ...`

- `hotfix-prod <hotfix-name>`
  - 從 `main` 建立 `hotfix/<name>`。

- `demo-day-pptx`
  - 輸出流程提醒（先做 implementation_plan 再做 PPTX）。

- `start-task <feat|fix|refactor|docs> <task-name-kebab> [--stash]`
  - 從最新 `dev` 建立任務分支，必要時先 stash 再 pop。

- `promote-env <dev|demo> <demo|main> [--force-main]`
  - 支援 `dev -> demo` 或 `demo -> main`。
  - 會先列出 log 與敏感檔案，再 merge/push，最後切回 `dev`。

- `monthly-report <year> <month>`
  - 產生月報草稿檔案：`docs/monthly-report-YYYY-MM.md`。

- `now-push <target-branch> <prefix> "<summary>" ["detail1" ...]`
  - `prefix`: `FIX|FEAT|DOCS|STYLE|REFACTOR`
  - 自動 `git add -A`、commit、push 到目標分支。

