# Translation Files

## 📁 Files in This Directory

- `seoulcommerce-payment-gateway-for-tosspayments.pot` - Translation template (for new languages)
- `seoulcommerce-payment-gateway-for-tosspayments-ko_KR.po` - Korean translation source
- `seoulcommerce-payment-gateway-for-tosspayments-ko_KR.mo` - **Needs compilation!** ⚠️

Note: Older `seoulcommerce-tosspayments*` language files are still present for backward compatibility. Remove them only after you're sure you no longer need the previous text domain.

## ⚠️ Important: MO File Compilation Required

The `.mo` file is the compiled binary version that WordPress actually uses. The `.po` file alone will NOT work.

### Quick Compile Methods:

#### Method 1: Poedit (Easiest)
1. Download [Poedit](https://poedit.net/) (free)
2. Open `seoulcommerce-payment-gateway-for-tosspayments-ko_KR.po`
3. Save (automatically creates `.mo`)
4. Done! ✅

#### Method 2: Loco Translate Plugin
1. Install Loco Translate in WordPress
2. Find this plugin in Loco Translate
3. Edit Korean translation
4. Click "Sync"
5. Done! ✅

#### Method 3: WordPress.org Auto-Load
When plugin is on WordPress.org, translations auto-download.
No manual steps needed! ✅

## 📖 Full Instructions

See `TRANSLATION-GUIDE.md` in the parent directory for complete translation instructions.

## 🌏 Adding New Languages

1. Copy `seoulcommerce-payment-gateway-for-tosspayments.pot`
2. Rename to `seoulcommerce-payment-gateway-for-tosspayments-{locale}.po`
3. Translate all strings
4. Compile to `.mo` using Poedit
5. Test with WordPress in that language

## 💡 Locale Codes

- Korean: `ko_KR`
- Japanese: `ja`
- Chinese (Simplified): `zh_CN`
- Chinese (Traditional): `zh_TW`
- English (US): `en_US`

Full list: https://make.wordpress.org/polyglots/teams/

## ✅ Current Translations

- **Korean (ko_KR)** - 100% complete ✅
- **Your language?** - Contributions welcome!

---

**Need help?** See `TRANSLATION-GUIDE.md` or `KOREAN-TRANSLATION-COMPLETE.md`

