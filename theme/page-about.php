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

// Get customizer values with defaults.
$hero_title   = get_theme_mod( 'plainmark_about_hero_title', '業務を、使いやすく。' );
$hero_lead    = get_theme_mod( 'plainmark_about_hero_lead', 'まーさんです。業務システム、EC、予約システムなどの開発で、要件整理から設計、実装、テストまで一貫して携わってきました。複雑な仕様を整理し、利用者にも運用者にも扱いやすいWebシステムに落とし込むことを大切にしています。' );
$profile_mark = get_theme_mod( 'plainmark_about_profile_mark', 'M' );
$profile_name = get_theme_mod( 'plainmark_about_profile_name', 'まーさん' );
$profile_role = get_theme_mod( 'plainmark_about_profile_role', 'Web Engineer / Frontend & Backend' );
$profile_focus    = get_theme_mod( 'plainmark_about_profile_focus', '業務理解と設計' );
$profile_frontend = get_theme_mod( 'plainmark_about_profile_frontend', 'React / Vue / Next.js' );
$profile_backend  = get_theme_mod( 'plainmark_about_profile_backend', 'FastAPI / Java' );
$philosophy_title = get_theme_mod( 'plainmark_about_philosophy_title', '伝わる形にする。' );
$philosophy_text1 = get_theme_mod( 'plainmark_about_philosophy_text1', '開発で大切にしているのは、仕様をそのまま実装することではなく、背景にある業務や課題を理解したうえで、保守しやすく、使いやすい形に整理することです。' );
$philosophy_text2 = get_theme_mod( 'plainmark_about_philosophy_text2', '画面、API、DB、権限、運用フローはそれぞれ独立しているようで、実際には強くつながっています。だからこそ、フロントエンドとバックエンドを横断して全体像を見ながら設計することを意識しています。' );
$cta_title = get_theme_mod( 'plainmark_about_cta_title', '学びを残す。' );

// Strengths with defaults.
$strengths_defaults = array(
    array(
        'title' => '曖昧な要件を整理する',
        'text'  => '業務フローや既存仕様を読み解き、画面・API・データのつながりを整理します。関係者の認識をそろえながら、実装に落とし込める状態へ具体化することが得意です。',
    ),
    array(
        'title' => '使いやすさと保守性を両立する',
        'text'  => 'UI/UX、権限管理、業務ロジック、テストのしやすさを意識して設計します。短期的に動くだけでなく、あとから変更しやすい実装を目指しています。',
    ),
    array(
        'title' => 'チームで前に進める',
        'text'  => 'PL・チームリーダーとして、タスク整理、レビュー、技術相談、進捗管理を経験。メンバーが迷わず動けるように、情報を整理して共有することを大切にしています。',
    ),
);

$strengths = array();
for ( $i = 1; $i <= 3; $i++ ) {
    $default_title = $strengths_defaults[ $i - 1 ]['title'];
    $default_text  = $strengths_defaults[ $i - 1 ]['text'];
    $title = get_theme_mod( "plainmark_about_strength_{$i}_title", $default_title );
    $text  = get_theme_mod( "plainmark_about_strength_{$i}_text", $default_text );
    if ( $title || $text ) {
        $strengths[] = array(
            'title' => $title,
            'text'  => $text,
        );
    }
}

// Experiences with defaults.
$experiences_defaults = array(
    array(
        'label' => 'EC / Payment',
        'title' => '購入導線や決済に関わるシステムの設計・改善',
        'text'  => '大規模ECの基本設計や、自治体向けデジタル決済アプリの改善に携わりました。既存仕様を整理し、関係者と認識を合わせながら、画面仕様・API仕様・データ設計へ落とし込む経験を積んできました。',
    ),
    array(
        'label' => 'PL / FastAPI',
        'title' => '空調管理システムのリプレイスと機能改善',
        'text'  => 'PLとして、FastAPIによるAPI開発、Vue.jsによる画面実装、CASLを用いた権限管理、レビュー、メンバーサポートを担当。運用性と保守性を高める改善にも取り組みました。',
    ),
    array(
        'label' => 'Reservation',
        'title' => '予約システムのフロントエンド・API開発',
        'text'  => 'クルーズ予約やアクティビティ予約システムで、Next.jsを用いた予約画面と、AWS Lambda上で動作するバックエンドAPIを開発。外部API連携や仕様変更にも対応しました。',
    ),
    array(
        'label' => 'Legacy / Improve',
        'title' => '既存システムの理解と改善',
        'text'  => 'JavaやjQuery、Knockout.jsを用いた業務システムの開発・保守運用を経験。設計書整備、ブラウザ対応、運用改善、自動化ツール作成など、現場の困りごとを減らす改善にも取り組んできました。',
    ),
);

$experiences = array();
for ( $i = 1; $i <= 4; $i++ ) {
    $default_label = $experiences_defaults[ $i - 1 ]['label'];
    $default_title = $experiences_defaults[ $i - 1 ]['title'];
    $default_text  = $experiences_defaults[ $i - 1 ]['text'];
    $label = get_theme_mod( "plainmark_about_exp_{$i}_label", $default_label );
    $title = get_theme_mod( "plainmark_about_exp_{$i}_title", $default_title );
    $text  = get_theme_mod( "plainmark_about_exp_{$i}_text", $default_text );
    if ( $label || $title || $text ) {
        $experiences[] = array(
            'label' => $label,
            'title' => $title,
            'text'  => $text,
        );
    }
}
?>

<main id="main" class="about-page">
    <section class="about-hero">
        <div class="container container--wide about-hero__inner">
            <div class="about-hero__content">
                <p class="about-eyebrow"><?php esc_html_e( 'ABOUT ME', 'plainmark' ); ?></p>
                <h1 class="about-hero__title"><?php echo esc_html( $hero_title ); ?></h1>
                <p class="about-hero__lead"><?php echo esc_html( $hero_lead ); ?></p>
            </div>
            <aside class="about-profile-card" aria-label="<?php esc_attr_e( 'プロフィール概要', 'plainmark' ); ?>">
                <div class="about-profile-card__mark"><?php echo esc_html( $profile_mark ); ?></div>
                <p class="about-profile-card__name"><?php echo esc_html( $profile_name ); ?></p>
                <p class="about-profile-card__role"><?php echo esc_html( $profile_role ); ?></p>
                <dl class="about-profile-card__list">
                    <div>
                        <dt><?php esc_html_e( 'Focus', 'plainmark' ); ?></dt>
                        <dd><?php echo esc_html( $profile_focus ); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e( 'Frontend', 'plainmark' ); ?></dt>
                        <dd><?php echo esc_html( $profile_frontend ); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e( 'Backend', 'plainmark' ); ?></dt>
                        <dd><?php echo esc_html( $profile_backend ); ?></dd>
                    </div>
                </dl>
            </aside>
        </div>
    </section>

    <section class="about-section about-summary">
        <div class="container container--wide about-summary__grid">
            <div>
                <p class="about-eyebrow"><?php esc_html_e( 'PHILOSOPHY', 'plainmark' ); ?></p>
                <h2><?php echo esc_html( $philosophy_title ); ?></h2>
            </div>
            <div class="about-summary__text">
                <p><?php echo esc_html( $philosophy_text1 ); ?></p>
                <p><?php echo esc_html( $philosophy_text2 ); ?></p>
            </div>
        </div>
    </section>

    <?php if ( ! empty( $strengths ) ) : ?>
        <section class="about-section about-strengths">
            <div class="container container--wide">
                <div class="about-section__heading">
                    <p class="about-eyebrow"><?php esc_html_e( 'STRENGTHS', 'plainmark' ); ?></p>
                    <h2><?php esc_html_e( '強み', 'plainmark' ); ?></h2>
                </div>
                <div class="about-card-grid">
                    <?php foreach ( $strengths as $index => $strength ) : ?>
                        <article class="about-card">
                            <span class="about-card__number"><?php echo esc_html( str_pad( $index + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
                            <h3><?php echo esc_html( $strength['title'] ); ?></h3>
                            <p><?php echo esc_html( $strength['text'] ); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

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

    <?php if ( ! empty( $experiences ) ) : ?>
        <section class="about-section about-timeline">
            <div class="container container--wide">
                <div class="about-section__heading">
                    <p class="about-eyebrow"><?php esc_html_e( 'EXPERIENCE', 'plainmark' ); ?></p>
                    <h2><?php esc_html_e( '経験', 'plainmark' ); ?></h2>
                </div>
                <div class="about-timeline__list">
                    <?php foreach ( $experiences as $exp ) : ?>
                        <article class="about-timeline__item">
                            <time><?php echo esc_html( $exp['label'] ); ?></time>
                            <div>
                                <h3><?php echo esc_html( $exp['title'] ); ?></h3>
                                <p><?php echo esc_html( $exp['text'] ); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="about-section about-cta">
        <div class="container container--wide about-cta__inner">
            <div>
                <p class="about-eyebrow"><?php esc_html_e( 'BLOG', 'plainmark' ); ?></p>
                <h2><?php echo esc_html( $cta_title ); ?></h2>
            </div>
            <a class="about-cta__button" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
                <?php esc_html_e( '記事一覧を見る', 'plainmark' ); ?>
            </a>
        </div>
    </section>
</main>

<?php get_footer();
