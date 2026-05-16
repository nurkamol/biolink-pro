import { createContext, useContext } from 'react';
import type { BioBlock, BioPage, BioPageSettings } from '../../api/client';

export interface BuilderContextValue {
	page: BioPage;
	setBlocks: ( blocks: BioBlock[] ) => void;
	setSettings: ( settings: BioPageSettings ) => void;
	setTheme: ( slug: string ) => void;
	setSeo: ( seo: Record< string, unknown > ) => void;
	setTitle: ( title: string ) => void;
	bumpPreview: () => void;
	saving: boolean;
	savedAt: Date | null;
}

export const BuilderContext = createContext< BuilderContextValue | null >( null );

export function useBuilder(): BuilderContextValue {
	const ctx = useContext( BuilderContext );
	if ( ! ctx ) {
		throw new Error( 'useBuilder must be used inside <BuilderShell>' );
	}
	return ctx;
}
