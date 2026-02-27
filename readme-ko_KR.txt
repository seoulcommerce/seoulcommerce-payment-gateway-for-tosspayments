=== SeoulCommerce Payment Gateway for TossPayments ===
기여자: seoulcommerce
기부 링크: https://seoulcommerce.com/donate
태그: woocommerce, payment gateway, tosspayments, credit card, korea
최소 요구 버전: 6.0
테스트 완료 버전: 6.9
최소 PHP 버전: 7.4
안정 버전: 1.0.0
라이선스: GPLv2 or later
라이선스 URI: https://www.gnu.org/licenses/gpl-2.0.html

토스페이먼츠 v2 API를 사용하여 WooCommerce에서 카드 결제를 받으세요. WooCommerce 체크아웃 블록과 완벽하게 호환됩니다.

== 설명 ==

SeoulCommerce Payment Gateway for TossPayments는 토스페이먼츠 카드 결제 기능을 WooCommerce 스토어에 통합하는 결제 게이트웨이 플러그인입니다. 이 플러그인은 토스페이먼츠 버전 2 API/SDK를 사용하며 WooCommerce 체크아웃 블록과 호환됩니다.

참고: 이 플러그인은 SeoulCommerce에서 개발했으며, 토스페이먼츠와 제휴/공식 연동을 의미하지 않습니다.

= 주요 기능 =

* **카드 결제 지원** - 신용카드 및 체크카드 결제
* **토스페이먼츠 v2 API** - 최신 API 사용
* **체크아웃 블록 호환** - WooCommerce 블록 체크아웃과 완벽 호환
* **테스트 모드** - 실제 결제 전 샌드박스 환경에서 테스트
* **환불 지원** - 관리자 페이지에서 전액 및 부분 환불 가능
* **웹훅 지원** - 결제 상태 실시간 업데이트
* **보안** - 안전한 결제 처리 및 데이터 보호
* **완전한 한국어 지원** - 모든 텍스트 한국어 번역 제공
* **가맹점 가입 배너** - 특별 우대 수수료율로 가입 안내

= 토스페이먼츠란? =

토스페이먼츠는 대한민국의 선도적인 결제 솔루션 제공업체입니다. 신용카드, 체크카드, 간편결제, 계좌이체 등 다양한 결제 수단을 지원하며, 안정적이고 안전한 결제 서비스를 제공합니다.

= 왜 SeoulCommerce TossPayments를 선택해야 하나요? =

* ✅ **업계 최저 수수료율** - SeoulCommerce 제휴 링크를 통한 가입 시 특별 우대 수수료 적용
* ✅ **실시간 정산** - 빠른 정산 및 자동 입금
* ✅ **24시간 고객 지원** - 토스페이먼츠의 전문적인 고객 지원
* ✅ **강력한 보안** - PCI DSS 인증 및 최신 보안 기술
* ✅ **쉬운 설정** - 5분 안에 설치 및 설정 완료
* ✅ **워드프레스 표준 준수** - 워드프레스 코딩 표준 완벽 준수

= 시스템 요구사항 =

* WordPress 6.0 이상
* WooCommerce 8.0 이상
* PHP 7.4 이상
* 토스페이먼츠 가맹점 계정

= 특별 혜택 =

이 플러그인을 통해 토스페이먼츠 가맹점으로 가입하시면 **특별 우대 수수료율**을 받으실 수 있습니다!

**가입하기**: 플러그인 설치 후 WooCommerce 설정 페이지에서 가입 배너를 확인하세요.

== 설치 ==

**🎯 중요: 먼저 특별 제휴 링크로 가입하여 우대 수수료율을 받으세요!**
https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd
(업계 최저 수수료율과 SeoulCommerce 특별 혜택을 받으실 수 있습니다!)

= 자동 설치 =

1. **먼저 위의 특별 링크로 토스페이먼츠 가맹점 가입** (아직 안 하셨다면)
2. WordPress 관리자 페이지에서 플러그인 > 새로 추가로 이동
3. "SeoulCommerce TossPayments" 검색
4. "지금 설치" 클릭
5. 설치 완료 후 "활성화" 클릭

= 수동 설치 =

1. **먼저 위의 특별 링크로 토스페이먼츠 가맹점 가입** (아직 안 하셨다면)
2. 플러그인 파일을 `/wp-content/plugins/tosspayments-gateway-for-woocommerce` 디렉토리에 업로드
3. WordPress 관리자 페이지의 '플러그인' 화면에서 플러그인 활성화
4. WooCommerce > 설정 > 결제로 이동하여 TossPayments 설정
5. 토스페이먼츠 대시보드에서 API 키 받아서 입력 (클라이언트 키 및 시크릿 키)
6. 테스트 모드를 활성화하여 테스트하거나 비활성화하여 실제 결제 시작
7. 변경사항을 저장하고 결제 받기 시작!

== 설정 ==

1. **먼저 특별 제휴 링크로 가입** (아직 안 하셨다면):
   https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd

2. **WooCommerce > 설정 > 결제**로 이동
3. **"TossPayments"**를 클릭하여 설정
4. **설정 페이지 상단에 큰 가입 안내 배너가 보입니다** (특별 링크 포함)
5. 가입 후 토스페이먼츠 대시보드에서 API 키 받기
6. 결제 방법 활성화
7. 토스페이먼츠 API 자격증명 입력:
   * **테스트 클라이언트 키** (테스트용)
   * **테스트 시크릿 키** (테스트용)
   * **라이브 클라이언트 키** (실제 운영용)
   * **라이브 시크릿 키** (실제 운영용)
8. **테스트 모드** 활성화 여부 선택
9. 원하는 경우 제목 및 설명 사용자 정의
10. 변경사항 저장

= API 키 받기 =

1. [토스페이먼츠 가입하기](https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd) (특별 우대 수수료율!)
2. 가입 후 토스페이먼츠 대시보드에 로그인
3. 개발자 센터 > API 키 메뉴로 이동
4. 테스트 및 라이브 API 키 복사
5. WooCommerce 설정에 키 입력

== 자주 묻는 질문 ==

= 토스페이먼츠 가맹점 계정이 필요한가요? =

네, 이 플러그인을 사용하려면 토스페이먼츠 가맹점 계정이 필요합니다. 플러그인의 배너 링크를 통해 가입하시면 특별 우대 수수료율을 받으실 수 있습니다.

= 테스트 모드는 어떻게 사용하나요? =

WooCommerce 설정에서 "테스트 모드 활성화" 체크박스를 선택하고 테스트 API 키를 입력하세요. 테스트 모드에서는 실제 결제가 발생하지 않습니다.

= 환불은 어떻게 처리하나요? =

WooCommerce 관리자 페이지에서 주문 상세 페이지로 이동하여 "환불" 버튼을 클릭하세요. 전액 환불 또는 부분 환불을 선택할 수 있습니다.

= 어떤 카드를 지원하나요? =

국내 모든 신용카드 및 체크카드를 지원합니다 (비자, 마스터카드, JCB, 아멕스, 국내 카드사 등).

= WooCommerce 블록 체크아웃과 호환되나요? =

네, 이 플러그인은 WooCommerce 블록 체크아웃과 완벽하게 호환됩니다.

= 수수료는 얼마인가요? =

수수료는 토스페이먼츠와의 계약 조건에 따라 다릅니다. SeoulCommerce 제휴 링크를 통해 가입하시면 특별 우대 수수료율을 받으실 수 있습니다.

= 여러 통화를 지원하나요? =

현재는 한국 원화(KRW)만 지원합니다.

= 정산은 언제 되나요? =

정산 일정은 토스페이먼츠와의 계약 조건에 따라 다릅니다. 일반적으로 D+1 또는 D+2 정산을 제공합니다.

= 플러그인이 작동하지 않아요. 어떻게 해야 하나요? =

1. WooCommerce 플러그인이 활성화되어 있는지 확인
2. API 키가 올바르게 입력되었는지 확인
3. 테스트 모드에서 올바른 테스트 키를 사용하는지 확인
4. WooCommerce > 상태 > 로그에서 오류 확인
5. 문제가 지속되면 support@seoulcommerce.com으로 문의

= 기술 지원은 어디서 받을 수 있나요? =

* **플러그인 지원**: support@seoulcommerce.com
* **토스페이먼츠 지원**: 토스페이먼츠 고객센터 1544-7772
* **문서**: 플러그인 디렉토리의 README.md 파일 참조

== 스크린샷 ==

1. WooCommerce 결제 설정 페이지의 TossPayments 옵션
2. TossPayments 설정 페이지 - API 키 입력
3. 체크아웃 페이지의 TossPayments 결제 옵션
4. TossPayments 결제 창
5. 주문 관리자 페이지 - 환불 버튼
6. 가맹점 가입 배너 (특별 우대 수수료율)

== 변경 로그 ==

= 1.0.0 - 2024-01-01 =
* 최초 릴리스
* 토스페이먼츠 v2 API 통합
* 카드 결제 지원
* WooCommerce 체크아웃 블록 호환
* 테스트 모드 지원
* 환불 기능 (전액 및 부분 환불)
* 결제 상태 업데이트를 위한 웹훅 지원
* 포괄적인 백엔드 설정 옵션
* 워드프레스 코딩 표준 준수
* 완전한 한국어 지원
* 체크아웃 페이지에 토스페이먼츠 로고 표시
* 특별 우대 수수료율 안내 가맹점 가입 배너
  * 신규 가맹점을 위한 한국어 관리자 공지
  * API 키 설정 시 자동 숨김 기능
  * 사용자별 닫기 기능 및 기억
  * UTM 파라미터를 통한 제휴 추적 (seoulwd 대리점 코드)
  * 혜택 목록 및 눈에 띄는 CTA가 포함된 반응형 디자인

== 업그레이드 안내 ==

= 1.0.0 =
최초 릴리스입니다. 업그레이드 절차가 필요하지 않습니다.

== 개인정보 보호 ==

이 플러그인은:

* 결제 처리를 위해 주문 정보를 토스페이먼츠 서버로 전송합니다
* 로컬에 고객 결제 정보를 저장하지 않습니다 (PCI DSS 준수)
* 플러그인 기능 향상을 위해 익명화된 사용 데이터를 수집할 수 있습니다
* 외부 서비스(토스페이먼츠 API)와 통신합니다

자세한 내용은 [토스페이먼츠 개인정보 처리방침](https://www.tosspayments.com/privacy)을 참조하세요.

== 기여 ==

이 프로젝트에 기여하고 싶으시다면:

1. GitHub 저장소를 Fork하세요
2. 기능 브랜치를 생성하세요 (`git checkout -b feature/AmazingFeature`)
3. 변경사항을 커밋하세요 (`git commit -m 'Add some AmazingFeature'`)
4. 브랜치에 Push하세요 (`git push origin feature/AmazingFeature`)
5. Pull Request를 열어주세요

== 라이선스 ==

이 플러그인은 GPL v2 이상 라이선스 하에 배포됩니다.

