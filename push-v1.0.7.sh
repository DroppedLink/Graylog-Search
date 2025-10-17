#!/bin/bash
# Push v1.0.7 - Final repository cleanup

set -e

cd /Users/stephenwhite/Code/wordpress/plugins/graylog-search

echo "📋 Staging changes..."
git add -A

echo ""
echo "📊 Changes to be committed:"
git status --short

echo ""
echo "💾 Committing changes..."
git commit -m "Version 1.0.7 - Final repository cleanup

- Removed nested plugins/ directory
- Companion plugins relocated to parent directory
- Clean production-ready structure
- Version bump to 1.0.7"

echo ""
echo "🚀 Pushing to GitHub..."
git push origin master

echo ""
echo "🏷️  Creating and pushing tag..."
git tag v1.0.7
git push origin v1.0.7

echo ""
echo "✅ Done! GitHub repository is now clean!"
echo "🔗 View at: https://github.com/DroppedLink/Graylog-Search"

