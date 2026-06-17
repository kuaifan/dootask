import { generateAriaTree, renderAriaTree } from '@injected/ariaSnapshot';

// dootask AI 助手页面操作 · 描述层封装。
// buildSnapshot(root) → { text: ariaSnapshot YAML, elements: Map<ref,Element>, refs: Map<Element,ref> }
export function buildSnapshot(root?: Element, options?: any) {
  const o = Object.assign({ mode: 'ai' }, options || {});
  const tree = generateAriaTree((root || document.body) as Element, o);
  const rendered = renderAriaTree(tree, o);
  return {
    text: rendered.text,
    elements: tree.elements, // Map<string ref, Element>
    refs: tree.refs,         // Map<Element, string ref>
  };
}

export { generateAriaTree, renderAriaTree };
