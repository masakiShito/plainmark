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
                <p class="about-eyebrow"><?php esc_html_e( 'ABOUT ME', 'plainmark' ); ?></p>
                <h1 class="about-hero__title"><?php esc_html_e( '業務を、使いやすく。', 'plainmark' ); ?></h1>
                <p class="about-hero__lead">
                    <?php esc_html_e( 'まーさんです。業務システム、EC、予約システムなどの開発で、要件整理から設計、実装、テストまで一貫して携わってきました。複雑な仕様を整理し、利用者にも運用者にも扱いやすいWebシステムに落とし込むことを大切にしています。', 'plainmark' ); ?>
                </p>
            </div>
            <aside class="about-profile-card" aria-label="<?php esc_attr_e( 'プロフィール概要', 'plainmark' ); ?>">
                <div class="about-profile-card__mark">M</div>
                <p class="about-profile-card__name"><?php esc_html_e( 'まーさん', 'plainmark' ); ?></p>
                <p class="about-profile-card__role"><?php esc_html_e( 'Web Engineer / Frontend & Backend', 'plainmark' ); ?></p>
                <dl class="about-profile-card__list">
                    <div>
                        <dt><?php esc_html_e( 'Focus', 'plainmark' ); ?></dt>
                        <dd><?php esc_html_e( '業務理解と設計', 'plainmark' ); ?></dd>
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
                <p class="about-eyebrow"><?php esc_html_e( 'PHILOSOPHY', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '伝わる形にする。', 'plainmark' ); ?></h2>
            </div>
            <div class="about-summary__text">
                <p><?php esc_html_e( '開発で大切にしているのは、仕様をそのまま実装することではなく、背景にある業務や課題を理解したうえで、保守しやすく、使いやすい形に整理することです。', 'plainmark' ); ?></p>
                <p><?php esc_html_e( '画面、API、DB、権限、運用フローはそれぞれ独立しているようで、実際には強くつながっています。だからこそ、フロントエンドとバックエンドを横断して全体像を見ながら設計することを意識しています。', 'plainmark' ); ?></p>
            </div>
        </div>
    </section>

    <section class="about-section about-strengths">
        <div class="container container--wide">
            <div class="about-section__heading">
                <p class="about-eyebrow"><?php esc_html_e( 'STRENGTHS', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '強み', 'plainmark' ); ?></h2>
            </div>
            <div class="about-card-grid">
                <article class="about-card">
                    <span class="about-card__number">01</span>
                    <h3><?php esc_html_e( '曖昧な要件を整理する', 'plainmark' ); ?></h3>
                    <p><?php esc_html_e( '業務フローや既存仕様を読み解き、画面・API・データのつながりを整理します。関係者の認識をそろえながら、実装に落とし込める状態へ具体化することが得意です。', 'plainmark' ); ?></p>
                </article>
                <article class="about-card">
                    <span class="about-card__number">02</span>
                    <h3><?php esc_html_e( '使いやすさと保守性を両立する', 'plainmark' ); ?></h3>
                    <p><?php esc_html_e( 'UI/UX、権限管理、業務ロジック、テストのしやすさを意識して設計します。短期的に動くだけでなく、あとから変更しやすい実装を目指しています。', 'plainmark' ); ?></p>
                </article>
                <article class="about-card">
                    <span class="about-card__number">03</span>
                    <h3><?php esc_html_e( 'チームで前に進める', 'plainmark' ); ?></h3>
                    <p><?php esc_html_e( 'PL・チームリーダーとして、タスク整理、レビュー、技術相談、進捗管理を経験。メンバーが迷わず動けるように、情報を整理して共有することを大切にしています。', 'plainmark' ); ?></p>
                </article>
            </div>
        </div>
    </section>

    <?php
    $tech_terms = get_terms(
        array(
            'taxonomy'   => 'technology',
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => 15,
        )
    );

    $tech_groups = array();
    if ( ! is_wp_error( $tech_terms ) ) {
        foreach ( $tech_terms as $term ) {
            $group = trim( $term->description ) ?: 'Other';
            $tech_groups[ $group ][] = $term;
        }
    }

    $show_group_names = count( $tech_groups ) > 1;
    ?>
    <section class="about-section about-skills">
        <div class="container container--wide about-skills__grid">
            <div>
                <p class="about-eyebrow"><?php esc_html_e( 'TECH STACK', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '技術', 'plainmark' ); ?></h2>
                <p class="about-skills__note"><?php esc_html_e( '記事と Portfolio のタグから自動集計', 'plainmark' ); ?></p>
            </div>
            <div class="about-skill-groups">
                <?php if ( ! empty( $tech_groups ) ) : ?>
                    <?php foreach ( $tech_groups as $group_name => $terms ) : ?>
                        <div class="about-skill-group">
                            <?php if ( $show_group_names ) : ?>
                                <h3><?php echo esc_html( $group_name ); ?></h3>
                            <?php endif; ?>
                            <ul>
                                <?php foreach ( $terms as $term ) : ?>
                                    <?php
                                    $term_url = get_term_link( $term );
                                    if ( is_wp_error( $term_url ) ) {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <a href="<?php echo esc_url( $term_url ); ?>">
                                            <?php echo esc_html( $term->name ); ?>
                                        </a>
                                        <span class="about-skill-count">(<?php echo esc_html( (string) $term->count ); ?>)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="about-skills__empty"><?php esc_html_e( '技術タグを記事に設定すると、ここに自動表示されます。', 'plainmark' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="about-section about-timeline">
        <div class="container container--wide">
            <div class="about-section__heading">
                <p class="about-eyebrow"><?php esc_html_e( 'EXPERIENCE', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '経験', 'plainmark' ); ?></h2>
            </div>
            <div class="about-timeline__list">
                <article class="about-timeline__item">
                    <time>EC / Payment</time>
                    <div>
                        <h3><?php esc_html_e( '購入導線や決済に関わるシステムの設計・改善', 'plainmark' ); ?></h3>
                        <p><?php esc_html_e( '大規模ECの基本設計や、自治体向けデジタル決済アプリの改善に携わりました。既存仕様を整理し、関係者と認識を合わせながら、画面仕様・API仕様・データ設計へ落とし込む経験を積んできました。', 'plainmark' ); ?></p>
                    </div>
                </article>
                <article class="about-timeline__item">
                    <time>PL / FastAPI</time>
                    <div>
                        <h3><?php esc_html_e( '空調管理システムのリプレイスと機能改善', 'plainmark' ); ?></h3>
                        <p><?php esc_html_e( 'PLとして、FastAPIによるAPI開発、Vue.jsによる画面実装、CASLを用いた権限管理、レビュー、メンバーサポートを担当。運用性と保守性を高める改善にも取り組みました。', 'plainmark' ); ?></p>
                    </div>
                </article>
                <article class="about-timeline__item">
                    <time>Reservation</time>
                    <div>
                        <h3><?php esc_html_e( '予約システムのフロントエンド・API開発', 'plainmark' ); ?></h3>
                        <p><?php esc_html_e( 'クルーズ予約やアクティビティ予約システムで、Next.jsを用いた予約画面と、AWS Lambda上で動作するバックエンドAPIを開発。外部API連携や仕様変更にも対応しました。', 'plainmark' ); ?></p>
                    </div>
                </article>
                <article class="about-timeline__item">
                    <time>Legacy / Improve</time>
                    <div>
                        <h3><?php esc_html_e( '既存システムの理解と改善', 'plainmark' ); ?></h3>
                        <p><?php esc_html_e( 'JavaやjQuery、Knockout.jsを用いた業務システムの開発・保守運用を経験。設計書整備、ブラウザ対応、運用改善、自動化ツール作成など、現場の困りごとを減らす改善にも取り組んできました。', 'plainmark' ); ?></p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="about-section about-cta">
        <div class="container container--wide about-cta__inner">
            <div>
                <p class="about-eyebrow"><?php esc_html_e( 'BLOG', 'plainmark' ); ?></p>
                <h2><?php esc_html_e( '学びを残す。', 'plainmark' ); ?></h2>
            </div>
            <a class="about-cta__button" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
                <?php esc_html_e( '記事一覧を見る', 'plainmark' ); ?>
            </a>
        </div>
    </section>
</main>

<?php get_footer();
