const molview_origin = 'https://molview.libretexts.org';
const sketcher_src = molview_origin + '/';
let seq = 0;
class MolViewSketcherElement extends HTMLElement {
    iframe;
    connectedCallback() {
        const query = this.hasAttribute('readonly') ? '?readonly' : '';
        this.iframe = document.createElement('iframe');
        this.iframe.src = sketcher_src + query;
        this.style.position = 'relative';
        this.iframe.style.position = 'absolute';
        this.iframe.style.width = '100%';
        this.iframe.style.height = '100%';
        this.iframe.style.border = 'none';
        this.appendChild(this.iframe);
    }
    _call(method, args) {
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
                method: method,
                args: args
            }, molview_origin);
        });
    }
    save() {
        return this._call('save');
    }
    load(data) {
        return this._call('load', { data: data });
    }
    getSMILES() {
        return this._call('get_smiles');
    }
}
customElements.define('molview-sketcher', MolViewSketcherElement);

