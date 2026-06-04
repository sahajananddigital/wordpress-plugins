import { useState, useEffect } from '@wordpress/element';
import { Button, TextareaControl, TextControl, Panel, PanelBody, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export default function App() {
    const [ prompt, setPrompt ] = useState( '' );
    const [ email, setEmail ] = useState( '' );
    const [ phone, setPhone ] = useState( '' );
    const [ address, setAddress ] = useState( '' );
    const [ services, setServices ] = useState( '' );
    const [ referenceUrl, setReferenceUrl ] = useState( '' );

    const [ isGenerating, setIsGenerating ] = useState( false );
    const [ isFinalizing, setIsFinalizing ] = useState( false );
    const [ generatedUrl, setGeneratedUrl ] = useState( '' );
    const [ error, setError ] = useState( '' );
    const [ successMessage, setSuccessMessage ] = useState( '' );
    const [ activeTab, setActiveTab ] = useState( 'generate' ); 
    
    // Plan State
    const [ sitePlan, setSitePlan ] = useState( null );
    const [ view, setView ] = useState( 'input' ); // 'input', 'review'
    const [ previewMode, setPreviewMode ] = useState( false );
    const [ activePreviewPage, setActivePreviewPage ] = useState( 0 );

    // Settings
    const [ mcpEndpoint, setMcpEndpoint ] = useState( '' ); 

    useEffect( () => {
        if ( activeTab === 'settings' ) {
            apiFetch( { path: '/ai-site-gen/v1/settings' } )
                .then( ( data ) => {
                    setMcpEndpoint( data.mcp_endpoint || 'http://127.0.0.1:11434/v1/chat/completions' );
                } )
                .catch( ( err ) => console.error( err ) );
        }
    }, [ activeTab ] );

    const handleSaveSettings = () => {
        apiFetch( {
            path: '/ai-site-gen/v1/settings',
            method: 'POST',
            data: { mcp_endpoint: mcpEndpoint },
        } ).then( () => {
            setSuccessMessage( 'Settings saved!' );
            setTimeout( () => setSuccessMessage( '' ), 3000 );
        } ).catch( ( err ) => setError( err.message ) );
    };

    const handleGeneratePlan = () => {
        setIsGenerating( true );
        setError( '' );
        setSitePlan( null );
 
        const data = new FormData();
        data.append( 'action', 'ai_site_gen_generate_plan' );
        data.append( 'nonce', window.aiSiteGen.nonce );
        data.append( 'prompt', prompt );
        data.append( 'email', email );
        data.append( 'phone', phone );
        data.append( 'address', address );
        data.append( 'services', services );
        data.append( 'reference_url', referenceUrl );

        fetch( window.aiSiteGen.ajaxUrl, {
            method: 'POST',
            body: data,
        } )
        .then( ( res ) => res.json() )
        .then( ( response ) => {
            setIsGenerating( false );
            if ( response.success ) {
                // Defensive check for LLM response structure
                const plan = response.data.plan;
                if ( plan && Array.isArray(plan.pages) ) {
                    setSitePlan( plan );
                    setView( 'review' );
                } else {
                    setError( 'AI returned an incomplete site plan. Please try again with a more detailed description.' );
                }
            } else {
                setError( response.data?.message || 'An error occurred during planning.' );
            }
        } )
        .catch( ( err ) => {
            setIsGenerating( false );
            setError( 'Network error: ' + err.message );
        } );
    };

    const handleFinalizeSite = () => {
        setIsFinalizing( true );
        setError( '' );

        const data = new FormData();
        data.append( 'action', 'ai_site_gen_finalize' );
        data.append( 'nonce', window.aiSiteGen.nonce );
        data.append( 'plan', JSON.stringify( sitePlan ) );

        fetch( window.aiSiteGen.ajaxUrl, {
            method: 'POST',
            body: data,
        } )
        .then( ( res ) => res.json() )
        .then( ( response ) => {
            setIsFinalizing( false );
            if ( response.success ) {
                setGeneratedUrl( response.data.url );
                setSuccessMessage( 'Website created successfully!' );
                setSitePlan( null );
                setView( 'input' );
            } else {
                setError( response.data?.message || 'An error occurred during finalization.' );
            }
        } )
        .catch( ( err ) => {
            setIsFinalizing( false );
            setError( 'Network error: ' + err.message );
        } );
    };

    const handleUpdatePlan = ( pageIndex, sectionIndex, key, value ) => {
        const newPlan = { ...sitePlan };
        newPlan.pages[pageIndex].sections[sectionIndex][key] = value;
        setSitePlan( newPlan );
    };

    const renderSectionMockup = ( section ) => {
        if (!section) return null;
        switch ( section.type ) {
            case 'hero':
                return (
                    <div className="mock-hero">
                        <h1>{ section.headline }</h1>
                        <p>{ section.subheadline }</p>
                        <div className="mock-button">{ section.buttonText }</div>
                    </div>
                );
            case 'features':
                return (
                    <div className="mock-features">
                        <h2>{ section.headline }</h2>
                        <div className="mock-grid">
                            { (section.items || []).map( (item, i) => (
                                <div key={i} className="mock-feature-card">
                                    <h3>{ item.title }</h3>
                                    <p>{ item.description }</p>
                                </div>
                            ))}
                        </div>
                    </div>
                );
            case 'about':
                return (
                    <div className="mock-about">
                        <div>
                            <h2>{ section.headline }</h2>
                            <p>{ section.content }</p>
                        </div>
                        <div className="mock-image-placeholder">About Image</div>
                    </div>
                );
            case 'testimonials':
                return (
                    <div className="mock-testimonials">
                        <h2>{ section.headline }</h2>
                        <div className="mock-grid">
                            { (section.quotes || []).map( (q, i) => (
                                <div key={i} className="mock-feature-card">
                                    <p className="mock-quote">"{ q.text }"</p>
                                    <strong>- { q.author }</strong>
                                </div>
                            ))}
                        </div>
                    </div>
                );
            case 'cta':
                return (
                    <div className="mock-cta">
                        <h2>{ section.headline }</h2>
                        <div className="mock-button">{ section.buttonText }</div>
                    </div>
                );
            default:
                return <div style={{ padding: '20px', border: '1px dashed #ccc' }}>Section: { section.type }</div>;
        }
    };

    return (
        <div className="ai-site-gen-app" style={{ maxWidth: '1200px', margin: '20px auto' }}>
            <div className="ai-site-gen-header" style={{ marginBottom: '20px', borderBottom: '1px solid #ddd', paddingBottom: '10px' }}>
                <h1 style={{ margin: 0 }}>AI Site Generator (Local MCP/LLM)</h1>
            </div>

            <div className="ai-site-gen-tabs" style={{ marginBottom: '20px' }}>
                <Button isPrimary={ activeTab === 'generate' } onClick={ () => setActiveTab( 'generate' ) } style={{ marginRight: '10px' }}>Generator</Button>
                <Button isPrimary={ activeTab === 'settings' } onClick={ () => setActiveTab( 'settings' ) }>Settings</Button>
            </div>

            { activeTab === 'settings' && (
                <div className="ai-site-gen-settings-panel">
                    <Panel>
                        <PanelBody title="MCP / Local LLM Configuration">
                            <TextControl label="Local LLM / MCP Endpoint" value={ mcpEndpoint } onChange={ ( val ) => setMcpEndpoint( val ) } />
                            <Button variant="primary" onClick={ handleSaveSettings }>Save Settings</Button>
                            { successMessage && <p style={{ color: 'green', marginTop: '10px' }}>{ successMessage }</p> }
                        </PanelBody>
                    </Panel>
                </div>
            ) }

            { activeTab === 'generate' && (
                <div className="ai-site-gen-content">
                    { view === 'input' && (
                        <Panel>
                            <PanelBody title="Site Details">
                                <TextControl label="Business Description" value={ prompt } onChange={ setPrompt } help="e.g., 'A luxury spa in Bali'." />
                                <hr style={{ margin: '20px 0', border: 0, borderTop: '1px solid #eee' }} />
                                <h3>Business Context</h3>
                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                                    <TextControl label="Email" value={ email } onChange={ setEmail } />
                                    <TextControl label="Phone" value={ phone } onChange={ setPhone } />
                                </div>
                                <TextControl label="Address" value={ address } onChange={ setAddress } />
                                <TextareaControl label="Services List (Mandatory)" value={ services } onChange={ setServices } help="AI will strictly use these services." />
                                <div style={ { marginTop: '20px' } }>
                                    <Button isPrimary isLarge onClick={ handleGeneratePlan } disabled={ isGenerating || ! prompt }>
                                        { isGenerating ? <Spinner /> : 'Generate Site Plan' }
                                    </Button>
                                </div>
                            </PanelBody>
                        </Panel>
                    ) }

                    { view === 'review' && sitePlan && sitePlan.pages && (
                        <div className="ai-site-gen-review">
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                                <h2>Review Site: { sitePlan.siteTitle }</h2>
                                <Button variant={ previewMode ? 'primary' : 'secondary' } onClick={ () => setPreviewMode(!previewMode) }>
                                    { previewMode ? 'Switch to Edit View' : 'Switch to Visual Preview' }
                                </Button>
                            </div>

                            { !previewMode ? (
                                <div className="edit-view">
                                    { sitePlan.pages.map( ( page, pIndex ) => (
                                        <Panel key={ pIndex } style={{ marginBottom: '20px' }}>
                                            <PanelBody title={ `Page: ${page.title}` } initialOpen={ pIndex === 0 }>
                                                { (page.sections || []).map( ( section, sIndex ) => (
                                                    <div key={ sIndex } style={{ padding: '15px', borderBottom: '1px solid #eee', background: '#f9f9f9', marginBottom: '10px' }}>
                                                        <strong>{ section.type.toUpperCase() }</strong>
                                                        { section.headline !== undefined && <TextControl label="Headline" value={ section.headline } onChange={ ( v ) => handleUpdatePlan(pIndex, sIndex, 'headline', v) } /> }
                                                        { section.subheadline !== undefined && <TextControl label="Subheadline" value={ section.subheadline } onChange={ ( v ) => handleUpdatePlan(pIndex, sIndex, 'subheadline', v) } /> }
                                                        { section.content !== undefined && <TextareaControl label="Content" value={ section.content } onChange={ ( v ) => handleUpdatePlan(pIndex, sIndex, 'content', v) } /> }
                                                        { section.buttonText !== undefined && <TextControl label="Button Text" value={ section.buttonText } onChange={ ( v ) => handleUpdatePlan(pIndex, sIndex, 'buttonText', v) } /> }
                                                    </div>
                                                ) ) }
                                            </PanelBody>
                                        </Panel>
                                    ) ) }
                                </div>
                            ) : (
                                <div className="visual-preview-container">
                                    <div style={{ display: 'flex', gap: '10px', marginBottom: '20px' }}>
                                        { sitePlan.pages.map( (p, i) => (
                                            <Button key={i} isPrimary={ activePreviewPage === i } onClick={() => setActivePreviewPage(i)}>{ p.title }</Button>
                                        ))}
                                    </div>
                                    <div className="preview-browser-frame">
                                        <div className="preview-toolbar">
                                            <div className="preview-dot red"></div>
                                            <div className="preview-dot yellow"></div>
                                            <div className="preview-dot green"></div>
                                            <div style={{ marginLeft: '20px', fontSize: '12px', color: '#666' }}>{ window.location.origin }/{ (sitePlan.pages[activePreviewPage] || {}).slug }</div>
                                        </div>
                                        <div className="preview-content">
                                            { (sitePlan.pages[activePreviewPage]?.sections || []).map( (s, i) => (
                                                <div key={i}>{ renderSectionMockup(s) }</div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            ) }

                            <div style={{ display: 'flex', gap: '15px', marginTop: '30px' }}>
                                <Button isPrimary isLarge onClick={ handleFinalizeSite } disabled={ isFinalizing }>
                                    { isFinalizing ? <Spinner /> : 'Finalize & Create Website' }
                                </Button>
                                <Button isSecondary isLarge onClick={ () => setView('input') }>Go Back</Button>
                            </div>
                        </div>
                    ) }

                    { error && <div className="notice notice-error inline" style={{ marginTop: '20px' }}><p dangerouslySetInnerHTML={ { __html: error } } /></div> }
                    { generatedUrl && <div className="ai-site-gen-success" style={{ marginTop: '20px', padding: '20px', background: '#fff', borderLeft: '4px solid #46b450', boxShadow: '0 1px 1px rgba(0,0,0,.04)' }}><h2 style={{ marginTop: 0 }}>Success!</h2><p>Website created!</p><a href={ generatedUrl } target="_blank" className="button button-primary" rel="noreferrer">View Site</a></div> }
                </div>
            ) }
        </div>
    );
}
