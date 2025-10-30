## Git Guide: Practical Version Control for Multi-Repo Workflows (EN)

This guide teaches essential Git concepts and best practices, with simple, copyable commands. It is optimized for real-world scenarios like working with multiple forks, selectively importing files, and keeping your repository clean and safe.$$

### Core Concepts
- **Repository**: Your project history (commits, branches, tags).
- **Commit**: A snapshot with a message explaining why the change happened.
- **Branch**: A line of development;$$z keep `main` stable and do work on feature branches.
- **Remote**: A pointer to another repository (e.g., `origin`, `upstream`, `fork1`).
- **HEAD**: Your current position (commit) and branch.

### Daily Hygiene
- Keep `main` clean, protected, and releasable.
- Create short-lived branches per task.
- Commit small, logical units with clear messages.
- Rebase your work on top of the latest `main` before opening PRs.

```bash
# Create a task branch
git checkout -b feat/integrate-forks

# Stage only what matters
git add path/to/file1 path/to/file2

# Write meaningful commit messages
git commit -m "feat: import X from fork1 and Y from fork2"
```

### Remotes: Working With Multiple Forks
Add other forks as remotes so you can fetch and selectively import files.

```bash
git remote -v
git remote add fork1 https://github.com/<user1>/<repo>.git
git remote add fork2 https://github.com/<user2>/<repo>.git
git remote add upstream https://github.com/<original-org>/<repo>.git   # optional
git fetch --all --prune
```

List branches from remotes:
```bash
git branch -r | grep fork1/
git branch -r | grep fork2/
```

### Selectively Import Files From Another Remote/Branch
Use `git restore` (recommended) or `git checkout` (legacy) to bring files or folders without merging the entire branch.

```bash
# Bring one file from fork1/main
git restore --source=fork1/main -- path/to/file/from/fork1.py

# Bring a folder from fork2/dev
git restore --source=fork2/dev -- path/to/folder/

# Alternative legacy syntax
git checkout fork1/main -- path/to/another/file.js
```

### Update Later (Pulling New Changes From Forks)
```bash
git fetch fork1
git fetch fork2
# Repeat restore commands for updated files you need
```

### Merge vs Rebase (When Updating Your Branch)
- **Merge**: Keeps a merge commit; history shows branches joining (easier, noisier).
- **Rebase**: Rewrites your commits on top of a new base; linear history (cleaner).

```bash
# Update local main
git checkout main
git pull origin main

# Rebase your feature branch on top of main
git checkout feat/integrate-forks
git rebase main

# If conflicts appear, resolve, then
git add <resolved-files>
git rebase --continue
```

### Cherry-pick (Pick Specific Commits)
```bash
# Bring a single commit from another branch
git cherry-pick <commit-sha>

# Abort if needed
git cherry-pick --abort
```

### Subtree vs Submodule (Whole External Projects)
- **Submodule**: Links to another repo at a specific commit. Good for libraries; requires extra commands.
- **Subtree**: Copies history into a subdirectory; simpler for syncing whole directories.

```bash
# Subtree example: add and later pull updates
git remote add lib https://github.com/<org>/<lib>.git
git subtree add --prefix=third_party/lib lib main --squash
git subtree pull --prefix=third_party/lib lib main --squash
```

### Clean Commits and Messages
- Use conventional commits (feat, fix, docs, refactor, chore, test).
- Message style: imperative mood, short subject, detailed body if needed.

```bash
git commit -m "fix: handle null token in auth middleware"
```

### Conflicts: Detect, Resolve, Verify
```bash
# After a rebase/merge conflict
git status

# Open diffs, edit, then mark resolved
git add path/to/conflicted/file

# Continue the operation
git rebase --continue   # or: git merge --continue
```

### Stash Work-in-Progress
```bash
git stash push -m "wip: partial integration"
git stash list
git stash apply stash@{0}  # or: git stash pop
```

### Bisect: Find the Commit That Broke Something
```bash
git bisect start
git bisect bad HEAD
git bisect good <known-good-sha>
# Test, then mark each step good/bad
git bisect good
git bisect bad
git bisect reset
```

### Safety Nets: Reflog & Restore
```bash
# See where HEAD has been
git reflog

# Recover a lost commit/branch
git checkout -b rescue <sha-from-reflog>
```****

### Tags and Releases
```bash
git tag -a v1.2.0 -m "Release 1.2.0"
git push origin v1.2.0
```

### .gitignore Essentials
```bash
# Example patterns
*.log
__pycache__/
node_modules/
.venv/
dist/
```

### Common Scenarios
- **Multi-fork selective import**: add remotes, fetch, `git restore --source=<remote>/<branch> -- <path>`, commit.
- **Sync with upstream**: add `upstream`, `git fetch upstream`, `git rebase upstream/main`.
- **Patch-only PR**: `git cherry-pick` specific commits and open a PR.
- **Vendor whole library**: prefer `git subtree` into `third_party/`.

### Pull Request Workflow
1) Rebase your branch on latest `main`.
2) Run tests/lint locally.
3) Push and open PR with a clear description.
4) Address review feedback with additional commits or history cleanup (`rebase -i`).

```bash
git push -u origin feat/integrate-**forks**
```

---

## راهنمای گیت: کنترل نسخه کاربردی برای سناریوهای چند ریپازیتوری (FA)

این راهنما مفاهیم ضروری Git و بهترین شیوه‌ها را با دستورات ساده و قابل کپی ارائه می‌کند. تمرکز روی سناریوهای واقعی مثل کار با چند فورک، وارد کردن انتخابی فایل‌ها، و نگه‌داشتن ریپو تمیز و ایمن است.

### مفاهیم پایه
- **Repository (ریپازیتوری)**: تاریخچه پروژه (کامیت‌ها، برنچ‌ها، تگ‌ها).
- **Commit (کامیت)**: اسنپ‌شات تغییرات با یک پیام که «چرایی» تغییر را توضیح می‌دهد.
- **Branch (برنچ)**: خط توسعه؛ `main` را پایدار نگه دارید و کارها را در برنچ‌های فیچر انجام دهید.
- **Remote (ریموت)**: اشاره‌گر به یک ریپوی دیگر (مثل `origin`، `upstream`، `fork1`).
- **HEAD**: موقعیت فعلی شما (کامیت) و برنچ فعال.

### بهداشت روزانه
- `main` را تمیز، محافظت‌شده و همیشه قابل انتشار نگه دارید.
- برای هر تسک یک برنچ کوتاه‌عمر بسازید.
- کوچک و منطقی کامیت کنید و پیام‌های واضح بنویسید.
- قبل از PR، برنچ خود را روی آخرین `main` ری‌بیس کنید.

```bash
# ساخت برنچ تسکی
git checkout -b feat/integrate-forks

# استیج کردن هدفمند
git add path/to/file1 path/to/file2

# پیام‌های معنادار
git commit -m "feat: import X from fork1 and Y from fork2"
```

### ریموت‌ها: کار با چند فورک
فورک‌های دیگر را به‌عنوان ریموت اضافه کنید تا بتوانید Fetch بگیرید و فایل‌ها را انتخابی وارد کنید.

```bash
git remote -v
git remote add fork1 https://github.com/<user1>/<repo>.git
git remote add fork2 https://github.com/<user2>/<repo>.git
git remote add upstream https://github.com/<original-org>/<repo>.git   # اختیاری
git fetch --all --prune
```

نمایش برنچ‌های ریموت‌ها:
```bash
git branch -r | grep fork1/
git branch -r | grep fork2/
```

### وارد کردن انتخابی فایل از ریموت/برنچ دیگر
برای آوردن فایل یا پوشه بدون مرج کردن کل برنچ، از `git restore` (توصیه‌شده) یا `git checkout` (قدیمی‌تر) استفاده کنید.

```bash
# آوردن یک فایل از fork1/main
git restore --source=fork1/main -- path/to/file/from/fork1.py

# آوردن یک پوشه از fork2/dev
git restore --source=fork2/dev -- path/to/folder/

# دستور جایگزین قدیمی
git checkout fork1/main -- path/to/another/file.js
```

### به‌روزرسانی‌های بعدی (کشیدن تغییرات جدید از فورک‌ها)
```bash
git fetch fork1
git fetch fork2
# برای فایل‌های به‌روز شده، دوباره از دستورات restore استفاده کنید
```

### مرج در برابر ری‌بیس (وقتی برنچ‌تان را آپدیت می‌کنید)
- **Merge (مرج)**: یک merge commit می‌سازد؛ تاریخچه شاخه‌ها را نشان می‌دهد (ساده‌تر، اما شلوغ‌تر).
- **Rebase (ری‌بیس)**: کامیت‌های شما را روی پایه جدید بازنویسی می‌کند؛ تاریخچه خطی (تمیزتر).

```bash
# به‌روزرسانی main محلی
git checkout main
git pull origin main

# ری‌بیس برنچ کاری روی main
git checkout feat/integrate-forks
git rebase main

# در صورت تعارض، حل کنید و سپس
git add <resolved-files>
git rebase --continue
```

### Cherry-pick (برداشتن کامیت‌های خاص)
```bash
# آوردن یک کامیت خاص از برنچ دیگر
git cherry-pick <commit-sha>

# در صورت نیاز لغو کنید
git cherry-pick --abort
```

### Subtree در برابر Submodule (وارد کردن کل پروژه‌های خارجی)
- **Submodule**: لینک به یک ریپو در یک کامیت مشخص. برای کتابخانه‌ها خوب است؛ دستورات اضافه دارد.
- **Subtree**: تاریخچه را داخل یک پوشه کپی می‌کند؛ برای همگام‌سازی پوشه‌های کامل ساده‌تر است.

```bash
# مثال Subtree: اضافه و سپس کشیدن آپدیت‌ها
git remote add lib https://github.com/<org>/<lib>.git
git subtree add --prefix=third_party/lib lib main --squash
git subtree pull --prefix=third_party/lib lib main --squash
```

### تمیزی کامیت‌ها و پیام‌ها
- از الگوی پیام‌های متعارف (feat, fix, docs, refactor, chore, test) استفاده کنید.
- موضوع کوتاه و در حالت امری؛ در صورت نیاز بدنه توضیحی اضافه کنید.

```bash
git commit -m "fix: handle null token in auth middleware"
```

### تعارض‌ها: تشخیص، حل، راستی‌آزمایی
```bash
# پس از تعارض در rebase/merge
git status

# Diff را باز کنید، ویرایش کنید، سپس resolved علامت بزنید
git add path/to/conflicted/file

# ادامه عملیات
git rebase --continue   # یا: git merge --continue
```

### Stash: ذخیره موقت کار نیمه‌تمام
```bash
git stash push -m "wip: partial integration"
git stash list
git stash apply stash@{0}  # یا: git stash pop
```

### Bisect: پیدا کردن کامیت خراب‌کننده
```bash
git bisect start
git bisect bad HEAD
git bisect good <known-good-sha>
# هر مرحله تست کنید و good/bad بزنید
git bisect good
git bisect bad
git bisect reset
```

### تورهای نجات: Reflog و بازیابی
```bash
# مسیر حرکت HEAD را ببینید
git reflog

# بازیابی کامیت/برنچ از دست‌رفته
git checkout -b rescue <sha-from-reflog>
```

### تگ‌ها و انتشارها
```bash
git tag -a v1.2.0 -m "Release 1.2.0"
git push origin v1.2.0
```

### نکات ضروری .gitignore
```bash
# الگوهای نمونه
*.log
__pycache__/
node_modules/
.venv/
dist/
```

### سناریوهای رایج
- **وارد کردن انتخابی از چند فورک**: ریموت‌ها را اضافه کنید، fetch بگیرید، `git restore --source=<remote>/<branch> -- <path>`، سپس commit.
- **همگام‌سازی با upstream**: `upstream` را اضافه کنید، `git fetch upstream`، `git rebase upstream/main`.
- **PR فقط-پچ**: با `git cherry-pick` کامیت‌های خاص را بیاورید و PR بدهید.
- **وندر کردن یک کتابخانه کامل**: ترجیحاً با `git subtree` داخل `third_party/`.

### چرخه Pull Request
1) برنچ خود را روی آخرین `main` ری‌بیس کنید.
2) تست‌ها/لینت را محلی اجرا کنید.
3) Push کنید و PR با توضیح روشن بسازید.
4) بازخوردها را با کامیت‌های تکمیلی یا تمیزکردن تاریخچه (`rebase -i`) اعمال کنید.

```bash
git push -u origin feat/integrate-forks
```


