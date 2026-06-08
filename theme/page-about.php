<?php
/**
 * About page template.
 *
 * This template is automatically used for a page with the slug "about".
 *
 * @package plainmark
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="about-page">
    <section class="about-hero">
        <div class="container container--wide about-hero__inner">
            <div class="about-hero__content">
                <p class="about-eyebrow"><?php esc_html_e( 'ABOUT', 'plainmark' ); ?></p>
                <h1 class="about-hero__title"><?php esc_html_e( '設計から実装まで、事業に寄り添ってつくるWebエンジニア。', 'plainmark' ); ?></h1>
                <p class="about-hero__lead">
                    <?php esc_html_e( 'React / Vue を用いたフロントエンド開発と、Python（FastAPI）・Java によるバックエンド開発を軸に、業務システム・EC・予約システムの開発に携わってきました。要件整理、設計、実装、テストまで一貫して対応できることが強みです。', 'plainmark' ); ?>
                </p>
            </div>
            <aside class="about-profile-card" aria-label="<?php esc_attr_e( 'プロフィール概要', 'plainmark' ); ?>">
                <div class="about-profile-card__mark">MS</div>
                <p class="about-profile-card__name"><?php esc_html_e( '師藤 真基', 'plainmark' ); ?></p>
                <p class="about-profile-card__role"><?php esc_html_e( 'Web Engineer / PL', 'plainmark' ); ?></p>
                <dl class="about-profile-card__list">
                    <div>
                        <dt><?php esc_html_e( 'Experience', 'plainmark' ); ?></dt>
                        <dd><?php esc_html_e( '約6年', 'plainmark' ); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e( 'Frontend', 'plainmark' ); ?></dt>
                        <dd><?php esc_html_e( 'React / Vue / Next.js', 'plainmark' ); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e( 'Backend', 'plainmark' ); ?></dt>
                        <dd><?php esc_html_e( 'FastAPI / Java', 'plainmark' ); ?></dd>
                    </div>
                </dl>
            </aside>
        </div>
    </section>

    <section class="about-section about-summary">
        <div class="container container--wide about-summary__grid">
            <div>
                <p class="about-eyebrow"><?php esc_html_e( 'SUMMARY', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '業務システムからWeb予約、ECまで。', 'plainmark' ); ?></h2>
            </div>
            <div class="about-summary__text">
                <p><?php esc_html_e( 'Webシステム開発に約6年間従事し、業務システム、ECシステム、予約システムなどの開発で要件整理・基本設計・実装・テストを担当してきました。', 'plainmark' ); ?></p>
                <p><?php esc_html_e( '直近ではPLとして、FastAPIによるAPI開発やVue.jsによる画面開発、CASLを用いた権限管理基盤の整備などを推進。設計と実装の両面から、保守性と品質を意識した開発に取り組んでいます。', 'plainmark' ); ?></p>
            </div>
        </div>
    </section>

    <section class="about-section about-strengths">
        <div class="container container--wide">
            <div class="about-section__heading">
                <p class="about-eyebrow"><?php esc_html_e( 'STRENGTHS', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '得意なこと', 'plainmark' ); ?></h2>
            </div>
            <div class="about-card-grid">
                <article class="about-card">
                    <span class="about-card__number">01</span>
                    <h3><?php esc_html_e( '設計から実装まで一貫対応', 'plainmark' ); ?></h3>
                    <p><?php esc_html_e( '要件整理・仕様検討から基本設計、実装、テストまで一連の工程を経験。業務フローと運用を意識した設計を大切にしています。', 'plainmark' ); ?></p>
                </article>
                <article class="about-card">
                    <span class="about-card__number">02</span>
                    <h3><?php esc_html_e( 'フロント・バックエンド横断', 'plainmark' ); ?></h3>
                    <p><?php esc_html_e( 'React / Vue / Next.js と、Python（FastAPI）・JavaによるAPI開発の両方を経験。画面とAPIの整合性を意識して開発できます。', 'plainmark' ); ?></p>
                </article>
                <article class="about-card">
                    <span class="about-card__number">03</span>
                    <h3><?php esc_html_e( 'PL・リーダー経験', 'plainmark' ); ?></h3>
                    <p><?php esc_html_e( '小〜中規模チームでタスク割り振り、レビュー、進捗管理、技術相談を担当。品質と納期の両立を意識して推進してきました。', 'plainmark' ); ?></p>
                </article>
            </div>
        </div>
    </section>

    <section class="about-section about-skills">
        <div class="container container--wide about-skills__grid">
            <div>
                <p class="about-eyebrow"><?php esc_html_e( 'SKILLS', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '技術スタック', 'plainmark' ); ?></h2>
            </div>
            <div class="about-skill-groups">
                <div class="about-skill-group">
                    <h3><?php esc_html_e( 'Frontend', 'plainmark' ); ?></h3>
                    <ul>
                        <li>React</li>
                        <li>Vue.js</li>
                        <li>Next.js</li>
                        <li>TypeScript</li>
                        <li>HTML / CSS</li>
                    </ul>
                </div>
                <div class="about-skill-group">
                    <h3><?php esc_html_e( 'Backend', 'plainmark' ); ?></h3>
                    <ul>
                        <li>Python</li>
                        <li>FastAPI</li>
                        <li>Java</li>
                        <li>Spring MVC</li>
                        <li>C# / ASP.NET</li>
                    </ul>
                </div>
                <div class="about-skill-group">
                    <h3><?php esc_html_e( 'Database / Infra', 'plainmark' ); ?></h3>
                    <ul>
                        <li>PostgreSQL</li>
                        <li>Oracle</li>
                        <li>MySQL</li>
                        <li>AWS Lambda</li>
                        <li>Docker / Git</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="about-section about-timeline">
        <div class="container container--wide">
            <div class="about-section__heading">
                <p class="about-eyebrow"><?php esc_html_e( 'CAREER', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '主な経験', 'plainmark' ); ?></h2>
            </div>
            <div class="about-timeline__list">
                <article class="about-timeline__item">
                    <time>2025 - 2026</time>
                    <div>
                        <h3><?php esc_html_e( '大規模EC販売システム / ペイメントアプリ', 'plainmark' ); ?></h3>
                        <p><?php esc_html_e( '仕様整理、基本設計、API仕様設計、既存システム改善、レビュー、セキュリティ課題整理などを担当。', 'plainmark' ); ?></p>
                    </div>
                </article>
                <article class="about-timeline__item">
                    <time>2025</time>
                    <div>
                        <h3><?php esc_html_e( '空調管理システム', 'plainmark' ); ?></h3>
                        <p><?php esc_html_e( 'PLとして約15本のAPIと複数画面の開発を推進。権限管理基盤、UI/UX改善、テストコード拡充にも取り組みました。', 'plainmark' ); ?></p>
                    </div>
                </article>
                <article class="about-timeline__item">
                    <time>2023 - 2025</time>
                    <div>
                        <h3><?php esc_html_e( 'クルーズ予約・アクティビティ予約システム', 'plainmark' ); ?></h3>
                        <p><?php esc_html_e( 'AWS Lambda上で動作するバックエンドAPIや、Next.jsを用いたWeb予約画面を開発。', 'plainmark' ); ?></p>
                    </div>
                </article>
                <article class="about-timeline__item">
                    <time>2019 - 2023</time>
                    <div>
                        <h3><?php esc_html_e( '業務システム開発・保守運用', 'plainmark' ); ?></h3>
                        <p><?php esc_html_e( 'Java、Spring MVC、jQuery、Knockout.jsなどを用いた業務システム開発、保守運用、リリース対応を経験。', 'plainmark' ); ?></p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="about-section about-cta">
        <div class="container container--wide about-cta__inner">
            <div>
                <p class="about-eyebrow"><?php esc_html_e( 'BLOG', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '学んだことを、わかりやすく残しています。', 'plainmark' ); ?></h2>
            </div>
            <a class="about-cta__button" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
                <?php esc_html_e( '記事一覧を見る', 'plainmark' ); ?>
            </a>
        </div>
    </section>
</main>

<?php get_footer();
