/**
 * Language setting for the core Code block.
 *
 * @package plainmark
 * @since 0.1.0
 */

type BlockAttributes = Record<string, unknown> & {
  codeLanguage?: string;
};

interface BlockEditProps {
  name: string;
  attributes: BlockAttributes;
  setAttributes: (attributes: Partial<BlockAttributes>) => void;
}

interface ElementProps {
  [key: string]: unknown;
}

declare const wp: {
  blockEditor: {
    InspectorControls: unknown;
  };
  components: {
    PanelBody: unknown;
    SelectControl: unknown;
  };
  compose: {
    createHigherOrderComponent: (
      callback: (BlockEdit: unknown) => (props: BlockEditProps) => unknown,
      name: string
    ) => unknown;
  };
  element: {
    Fragment: unknown;
    createElement: (type: unknown, props?: ElementProps | null, ...children: unknown[]) => unknown;
  };
  hooks: {
    addFilter: (hookName: string, namespace: string, callback: (...args: unknown[]) => unknown) => void;
  };
  i18n: {
    __: (text: string, domain: string) => string;
  };
};

const LANGUAGE_OPTIONS = [
  { label: 'Auto detect', value: 'auto' },
  { label: 'Plain text', value: 'plaintext' },
  { label: 'Bash / Shell', value: 'bash' },
  { label: 'C', value: 'c' },
  { label: 'C++', value: 'cpp' },
  { label: 'C#', value: 'csharp' },
  { label: 'CSS', value: 'css' },
  { label: 'Go', value: 'go' },
  { label: 'HTML', value: 'html' },
  { label: 'Java', value: 'java' },
  { label: 'JavaScript', value: 'javascript' },
  { label: 'JSON', value: 'json' },
  { label: 'JSX', value: 'jsx' },
  { label: 'PHP', value: 'php' },
  { label: 'Python', value: 'python' },
  { label: 'Ruby', value: 'ruby' },
  { label: 'SCSS', value: 'scss' },
  { label: 'SQL', value: 'sql' },
  { label: 'TypeScript', value: 'typescript' },
  { label: 'TSX', value: 'tsx' },
  { label: 'XML', value: 'xml' },
  { label: 'YAML', value: 'yaml' },
];

wp.hooks.addFilter(
  'blocks.registerBlockType',
  'plainmark/code-language-attribute',
  (...args: unknown[]): unknown => {
    const settings = args[0] as { attributes?: Record<string, unknown> };
    const name = args[1] as string;

    if (name !== 'core/code') return settings;

    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        codeLanguage: {
          type: 'string',
          source: 'attribute',
          selector: 'pre',
          attribute: 'data-code-language',
          default: 'auto',
        },
      },
    };
  }
);

const withCodeLanguageControl = wp.compose.createHigherOrderComponent(
  (BlockEdit: unknown) =>
    (props: BlockEditProps): unknown => {
      if (props.name !== 'core/code') {
        return wp.element.createElement(BlockEdit, props as unknown as ElementProps);
      }

      return wp.element.createElement(
        wp.element.Fragment,
        null,
        wp.element.createElement(BlockEdit, props as unknown as ElementProps),
        wp.element.createElement(
          wp.blockEditor.InspectorControls,
          null,
          wp.element.createElement(
            wp.components.PanelBody,
            {
              title: wp.i18n.__('Code language', 'plainmark'),
              initialOpen: true,
            },
            wp.element.createElement(wp.components.SelectControl, {
              label: wp.i18n.__('Language', 'plainmark'),
              value: props.attributes.codeLanguage ?? 'auto',
              options: LANGUAGE_OPTIONS,
              help: wp.i18n.__(
                'Auto detect uses the code content. Choose a language to override it.',
                'plainmark'
              ),
              onChange: (codeLanguage: string) => props.setAttributes({ codeLanguage }),
            })
          )
        )
      );
    },
  'withCodeLanguageControl'
);

wp.hooks.addFilter(
  'editor.BlockEdit',
  'plainmark/code-language-control',
  withCodeLanguageControl as (...args: unknown[]) => unknown
);

wp.hooks.addFilter(
  'blocks.getSaveContent.extraProps',
  'plainmark/code-language-props',
  (...args: unknown[]): unknown => {
    const extraProps = args[0] as ElementProps;
    const blockType = args[1] as { name: string };
    const attributes = args[2] as BlockAttributes;

    if (blockType.name !== 'core/code') return extraProps;

    if (!attributes.codeLanguage || attributes.codeLanguage === 'auto') return extraProps;

    return { ...extraProps, 'data-code-language': attributes.codeLanguage };
  }
);
