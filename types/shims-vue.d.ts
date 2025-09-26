// TypeScript declaration so editor (TS language service used by Vetur) can understand .vue SFC imports
// This enables better IntelliSense and can help some cases of path resolution.
declare module '*.vue' {
  import Vue from 'vue';
  export default Vue;
}
