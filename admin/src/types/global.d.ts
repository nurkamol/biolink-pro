export {};

declare global {
    interface BioLinkProCaps {
        managePages: boolean;
        publishPages: boolean;
        manageThemes: boolean;
        viewAnalytics: boolean;
        manageIntegrations: boolean;
        useAi: boolean;
    }

    interface BioLinkProGlobal {
        restBase: string;
        restNonce: string;
        adminUrl: string;
        pluginUrl: string;
        version: string;
        caps: BioLinkProCaps;
    }

    interface Window {
        BIOLINK_PRO: BioLinkProGlobal;
    }
}
