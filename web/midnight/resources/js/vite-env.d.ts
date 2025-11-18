/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<{}, {}, any>;
    export default component;
}

interface ImportMetaEnv {
    readonly VITE_APP_TITLE: string;
    readonly VITE_MIDNIGHT_API_URL: string;
    readonly VITE_MIDNIGHT_NETWORK: 'mainnet' | 'testnet' | 'devnet';
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
