# Changelog

All notable changes to SeoulCommerce TossPayments will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2026-08-28

### Security
- Security improvements to checkout and payment data handling.
- Hardened webhook verification.

## [1.0.1] - 2026-03-05

### Fixed
- Resolved frontend JavaScript variable mismatch that caused `wcTossPaymentsParams is not defined` errors.
- Updated readme/plugin URLs and external-service documentation for WordPress.org review compliance.
- Hardened webhook payload sanitization flow.

## [1.0.0] - 2026-02-27

### Added
- Initial release
- TossPayments v2 API integration
- Card payment support
- WooCommerce checkout blocks compatibility
- Test mode support
- Refund functionality (full and partial refunds from admin)
- Webhook support for payment status updates
- Comprehensive backend configuration options
- WordPress coding standards compliance
- Full localization support
- Complete Korean (ko_KR) translation included
- Translation-ready for other languages (.pot template provided)
- Korean readme file (readme-ko_KR.txt)
- TossPayments logo display on checkout pages
- Merchant signup banner with special affiliate rate promotion
  - Korean-language admin notice for new merchants
  - Smart auto-hide when API keys are configured
  - Dismissible with per-user memory
  - Affiliate tracking via UTM parameters (seoulwd agency code)
  - Responsive design with benefits list and prominent CTA

