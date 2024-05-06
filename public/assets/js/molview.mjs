const molview_origin = 'https://preview.molview.com';
let seq = 0;
class MolViewSketcherElement extends HTMLElement {
    iframe;
    connectedCallback() {
        this.iframe = document.createElement('iframe');
        this.iframe.src = molview_origin + '/embed/v2/sketcher';
        this.style.position = 'relative';
        this.iframe.style.width = '100%';
        this.iframe.style.height = '100%';
        this.iframe.style.border = 'none';
        this.appendChild(this.iframe);
    }
    getSMILES() {
        const target = this.iframe.contentWindow;
        return new Promise(resolve => {
            const ref = ++seq;
            function listener(e) {
                if (e.source == target && e.data.ref == ref) {
                    window.removeEventListener('message', listener);
                    resolve(e.data.result);
                }
            }
            window.addEventListener('message', listener);
            target.postMessage({
                ref: ref,
                origin: location.origin,
                method: 'get_smiles'
            }, molview_origin);
        });
    }
}
customElements.define('molview-sketcher', MolViewSketcherElement);
