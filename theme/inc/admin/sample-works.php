<?php
/**
 * Sample works generator.
 *
 * @package plainmark
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add submenu page under Portfolio.
 */
function plainmark_add_sample_works_page() {
    add_submenu_page(
        'edit.php?post_type=portfolio',
        __( 'サンプルWorks作成', 'plainmark' ),
        __( 'サンプルWorks作成', 'plainmark' ),
        'manage_options',
        'plainmark-sample-works',
        'plainmark_render_sample_works_page'
    );
}
add_action( 'admin_menu', 'plainmark_add_sample_works_page' );

/**
 * Render sample works page.
 */
function plainmark_render_sample_works_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $created_count = 0;

    if ( isset( $_POST['plainmark_create_sample_works'] ) ) {
        check_admin_referer( 'plainmark_create_sample_works_action', 'plainmark_create_sample_works_nonce' );
        $created_count = plainmark_create_sample_works();
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'サンプルWorks作成', 'plainmark' ); ?></h1>
        <p><?php esc_html_e( 'Worksの表示確認用に、ケーススタディ形式のPortfolio投稿を作成します。既に同じスラッグの投稿がある場合は重複作成しません。', 'plainmark' ); ?></p>

        <?php if ( $created_count > 0 ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    printf(
                        /* translators: %d: created count */
                        esc_html__( '%d件のサンプルWorksを作成しました。', 'plainmark' ),
                        (int) $created_count
                    );
                    ?>
                </p>
            </div>
        <?php elseif ( isset( $_POST['plainmark_create_sample_works'] ) ) : ?>
            <div class="notice notice-info is-dismissible">
                <p><?php esc_html_e( '新規作成できるサンプルWorksはありませんでした。既に作成済みの可能性があります。', 'plainmark' ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'plainmark_create_sample_works_action', 'plainmark_create_sample_works_nonce' ); ?>
            <p>
                <button type="submit" name="plainmark_create_sample_works" class="button button-primary button-large">
                    <?php esc_html_e( 'サンプルWorksを作成する', 'plainmark' ); ?>
                </button>
            </p>
        </form>

        <hr>
        <h2><?php esc_html_e( '作成されるサンプル', 'plainmark' ); ?></h2>
        <ul style="list-style: disc; padding-left: 1.5em;">
            <li>Face Photo Sorter</li>
            <li>Meeting Room Reservation API</li>
            <li>Plainmark WordPress Theme</li>
        </ul>
    </div>
    <?php
}

/**
 * Create sample works.
 *
 * @return int Created count.
 */
function plainmark_create_sample_works() {
    $samples = array(
        array(
            'title'        => 'Face Photo Sorter',
            'slug'         => 'face-photo-sorter',
            'excerpt'      => 'PC内の写真から特定人物が写っている画像を検出し、フォルダへ分類するPythonツール。',
            'content'      => "PCに保存された大量の写真から、指定した人物が写っている画像だけを抽出・分類するための個人開発ツールです。\n\nスマホのカメラロールではなく、PCに集約した写真を対象にすることで、ローカル環境で安全に処理できる構成を想定しています。\n\n今後はGUI化、類似度調整、重複検出、処理ログの可視化などを追加し、家族写真やイベント写真の整理に使えるツールへ育てていく想定です。",
            'summary'      => '顔認識を使って、PC内の写真から特定人物が写っている画像を抽出・分類するPythonツールです。',
            'problem'      => '大量の写真の中から、特定の人物が写っている写真だけを手作業で探すのは時間がかかります。クラウドサービスにすべての写真を預けることにも抵抗があり、ローカルで安全に整理できる仕組みが必要でした。',
            'solution'     => 'Pythonで画像を走査し、顔認識により対象人物が含まれる写真を検出します。元画像は削除せず、指定フォルダへコピーする方式にすることで、安全に分類できる設計にしました。',
            'architecture' => '画像読み込み、顔検出、特徴量比較、分類コピー、ログ出力の責務を分ける構成を想定しています。将来的にCLIとGUIの両方から使えるように、処理ロジックをUIから独立させる方針です。',
            'features'     => "・対象人物の顔画像をもとに類似写真を検出\n・元画像を保持したままコピーで分類\n・大量画像を順次処理\n・処理結果をログとして確認可能",
            'learnings'    => '画像処理では精度だけでなく、誤判定時のリカバリーや元データを壊さない設計が重要だと感じました。個人用途のツールでも、安全設計とログ出力は大切です。',
            'next_steps'   => 'GUI化、類似度しきい値の調整画面、重複画像検出、処理中断・再開機能を追加したいです。',
            'role'         => '個人開発 / 設計・実装',
            'period'       => '2026',
            'github_url'   => 'https://github.com/masakiShito/face-photo-sorter',
            'demo_url'     => '',
            'techs'        => array( 'Python', 'OpenCV', 'CLI', 'Image Processing' ),
        ),
        array(
            'title'        => 'Meeting Room Reservation API',
            'slug'         => 'meeting-room-reservation-api',
            'excerpt'      => 'FastAPIとMySQLで構築する会議室予約システムのAPI設計ケーススタディ。',
            'content'      => "会議室の登録、予約、空き状況確認を行うためのAPI設計・実装を想定したケーススタディです。\n\n単なるCRUDではなく、予約の重複チェック、論理削除、利用者に返すエラーメッセージ、将来的な権限管理を意識した構成にしています。\n\n学習用途のプロジェクトでありながら、実務に近い責務分離とテストのしやすさを意識しています。",
            'summary'      => 'FastAPIとMySQLを使った、会議室予約システムのバックエンドAPIです。',
            'problem'      => '予約システムは一見シンプルですが、同一時間帯の重複予約、論理削除、エラーハンドリング、将来的な権限管理など、考慮点が多くあります。学習用でも実務に近い形で設計する必要がありました。',
            'solution'     => 'controller / service / model / schema に責務を分け、予約作成時には重複チェックをservice層に集約。APIレスポンスとエラー設計も統一し、画面側から扱いやすいAPIを目指しました。',
            'architecture' => 'FastAPIを中心に、SQLAlchemyでDBアクセスを行い、Pydanticスキーマで入出力を定義します。service層に業務ロジックを寄せることで、テストしやすい構成にしています。',
            'features'     => "・会議室CRUD\n・予約登録・更新・キャンセル\n・予約重複チェック\n・論理削除\n・日付範囲での空き状況取得",
            'learnings'    => 'APIは動くだけでは不十分で、画面側が扱いやすいレスポンス設計、異常系の整理、テストしやすい責務分離が重要だと再確認しました。',
            'next_steps'   => 'JWT認証、ロール別権限、カレンダー表示用API、pytestによるテスト拡充を追加する予定です。',
            'role'         => 'API設計 / バックエンド実装',
            'period'       => '2026',
            'github_url'   => '',
            'demo_url'     => '',
            'techs'        => array( 'Python', 'FastAPI', 'MySQL', 'Docker' ),
        ),
        array(
            'title'        => 'Plainmark WordPress Theme',
            'slug'         => 'plainmark-wordpress-theme',
            'excerpt'      => '技術ブログと制作物ケーススタディを分けて見せるためのWordPressテーマ。',
            'content'      => "技術ブログとポートフォリオを同じサイト内で運用するためのWordPressテーマです。\n\nBlogは読み物として、Worksは制作物のケーススタディとして見せることで、記事一覧と実績ページの役割を明確に分けています。\n\nGitHub Actionsによるロリポップへの自動デプロイにも対応し、テーマの更新をコード管理できる形にしています。",
            'summary'      => '技術ブログと制作物ケーススタディを分けて見せるためのWordPressテーマです。',
            'problem'      => '通常のWordPressテーマでは、記事一覧と制作物一覧が似た見た目になりやすく、ポートフォリオとしての差別化が難しいという課題がありました。',
            'solution'     => 'BlogとWorksでテンプレートを分け、Worksは課題・解決・設計・学びを見せるケーススタディ形式にしました。トップページ、About、Worksそれぞれに役割を持たせています。',
            'architecture' => 'WordPressのカスタム投稿タイプportfolioを使い、専用のarchive-portfolio.phpとsingle-portfolio.phpを用意。ケーススタディ用のメタ情報を管理画面から入力できるようにしました。',
            'features'     => "・モダンなトップページ\n・Aboutページ\n・Worksケーススタディ一覧\n・Works詳細ページ\n・GitHub Actionsによる自動デプロイ",
            'learnings'    => 'WordPressでも、テンプレート設計とカスタム投稿タイプを整理すれば、単なるブログではなくポートフォリオとして見せられることが分かりました。',
            'next_steps'   => 'Worksのスクリーンショット管理、技術タグの絞り込み、関連記事導線、OGP最適化を追加したいです。',
            'role'         => 'テーマ設計 / 実装 / CI/CD',
            'period'       => '2026',
            'github_url'   => 'https://github.com/masakiShito/plainmark',
            'demo_url'     => home_url( '/' ),
            'techs'        => array( 'WordPress', 'PHP', 'CSS', 'GitHub Actions' ),
        ),
    );

    $created_count = 0;

    foreach ( $samples as $sample ) {
        $existing = get_page_by_path( $sample['slug'], OBJECT, 'portfolio' );

        if ( $existing ) {
            continue;
        }

        $post_id = wp_insert_post(
            array(
                'post_type'    => 'portfolio',
                'post_status'  => 'publish',
                'post_title'   => $sample['title'],
                'post_name'    => $sample['slug'],
                'post_excerpt' => $sample['excerpt'],
                'post_content' => wpautop( $sample['content'] ),
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            continue;
        }

        $meta_map = array(
            'work_summary'      => $sample['summary'],
            'work_problem'      => $sample['problem'],
            'work_solution'     => $sample['solution'],
            'work_architecture' => $sample['architecture'],
            'work_features'     => $sample['features'],
            'work_learnings'    => $sample['learnings'],
            'work_next_steps'   => $sample['next_steps'],
            'work_role'         => $sample['role'],
            'work_period'       => $sample['period'],
            'work_github_url'   => $sample['github_url'],
            'work_demo_url'     => $sample['demo_url'],
        );

        foreach ( $meta_map as $key => $value ) {
            if ( '' !== $value ) {
                update_post_meta( $post_id, $key, $value );
            }
        }

        if ( ! empty( $sample['techs'] ) ) {
            wp_set_object_terms( $post_id, $sample['techs'], 'technology' );
        }

        $created_count++;
    }

    return $created_count;
}
