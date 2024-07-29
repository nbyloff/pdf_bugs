<script>
    class ReportLoadedHandler extends Paged.Handler {
        constructor(chunker, polisher, caller) {
            super(chunker, polisher, caller);
        }

        afterRendered(pages) {
            window.report = 'ready'; // var for PDF generation to monitor so we know when to save PDF
        }
    }

    Paged.registerHandlers(ReportLoadedHandler);
</script>

<script>
    class RepeatingTableHeaders extends Paged.Handler {
        constructor(chunker, polisher, caller) {
            super(chunker, polisher, caller);
            this.splitTablesRefs = [];
        }

        afterPageLayout(pageElement, page, breakToken, chunker) {
            console.log(page);
            this.chunker = chunker;
            this.splitTablesRefs = [];

            if (breakToken) {
                const node = breakToken.node;
                if (node) {
                    const tables = this.findAllAncestors(node, "table");
                    if (node.tagName === "TABLE") {
                        tables.push(node);
                    }

                    if (tables.length > 0) {
                        this.splitTablesRefs = tables.map(t => t.dataset.ref);

                        //checks if split inside thead and if so, set breakToken to next sibling element
                        let thead = node.tagName === "THEAD" ? node : this.findFirstAncestor(node, "thead");
                        if (thead) {
                            let lastTheadNode = thead.hasChildNodes() ? thead.lastChild : thead;
                            breakToken.node = this.nodeAfter(lastTheadNode, chunker.source);
                        }

                        this.hideEmptyTables(pageElement, node);
                    }
                }
            }
        }

        hideEmptyTables(pageElement, breakTokenNode) {
            this.splitTablesRefs.forEach(ref => {
                let table = pageElement.querySelector("[data-ref='" + ref + "']");
                if (table) {
                    let sourceBody = table.querySelector("tbody > tr");
                    if (!sourceBody || this.refEquals(sourceBody.firstElementChild, breakTokenNode)) {
                        table.style.visibility = "hidden";
                        table.style.position = "absolute";
                        let lineSpacer = table.nextSibling;
                        if (lineSpacer) {
                            lineSpacer.style.visibility = "hidden";
                            lineSpacer.style.position = "absolute";
                        }
                    }
                }
            });
        }

        refEquals(a, b) {
            return a && a.dataset && b && b.dataset && a.dataset.ref === b.dataset.ref;
        }

        findFirstAncestor(element, selector) {
            while (element.parentNode && element.parentNode.nodeType === 1) {
                if (element.parentNode.matches(selector)) {
                    return element.parentNode;
                }
                element = element.parentNode;
            }
            return null;
        }

        findAllAncestors(element, selector) {
            const ancestors = [];
            while (element.parentNode && element.parentNode.nodeType === 1) {
                if (element.parentNode.matches(selector)) {
                    ancestors.unshift(element.parentNode);
                }
                element = element.parentNode;
            }
            return ancestors;
        }

// The addition of repeating Table Headers is done here because this hook is triggered before overflow handling
        layout(rendered) {
            this.splitTablesRefs.forEach(ref => {
                const renderedTable = rendered.querySelector("[data-ref='" + ref + "']");
                if (renderedTable) {
                    // this event can be triggered multiple times
                    // added a flag repeated-headers to control when table headers already repeated in current page.
                    if (!renderedTable.getAttribute("repeated-headers")) {
                        const sourceTable = this.chunker.source.querySelector("[data-ref='" + ref + "']");
                        this.repeatColgroup(sourceTable, renderedTable);
                        this.repeatTHead(sourceTable, renderedTable);
                        renderedTable.setAttribute("repeated-headers", true);
                    }
                }
            });
        }

        repeatColgroup(sourceTable, renderedTable) {
            let colgroup = sourceTable.querySelectorAll("colgroup");
            let firstChild = renderedTable.firstChild;
            colgroup.forEach((colgroup) => {
                let clonedColgroup = colgroup.cloneNode(true);
                renderedTable.insertBefore(clonedColgroup, firstChild);
            });
        }

        repeatTHead(sourceTable, renderedTable) {
            let thead = sourceTable.querySelector("thead");
            if (thead) {
                let clonedThead = thead.cloneNode(true);
                renderedTable.insertBefore(clonedThead, renderedTable.firstChild);
            }
        }

// the functions below are from pagedjs utils/dom.js
        nodeAfter(node, limiter) {
            if (limiter && node === limiter) {
                return;
            }
            let significantNode = this.nextSignificantNode(node);
            if (significantNode) {
                return significantNode;
            }
            if (node.parentNode) {
                while ((node = node.parentNode)) {
                    if (limiter && node === limiter) {
                        return;
                    }
                    significantNode = this.nextSignificantNode(node);
                    if (significantNode) {
                        return significantNode;
                    }
                }
            }
        }

        nextSignificantNode(sib) {
            while ((sib = sib.nextSibling)) {
                if (!this.isIgnorable(sib)) return sib;
            }
            return null;
        }

        isIgnorable(node) {
            return (node.nodeType === 8) || // A comment node
                ((node.nodeType === 3) && this.isAllWhitespace(node)); // a text node, all whitespace
        }

        isAllWhitespace(node) {
            return !(/[^\t\n\r ]/.test(node.textContent));
        }
    }

    Paged.registerHandlers(RepeatingTableHeaders);
</script>

<script>

    window.createToc = function (config) {
        const content = config.content;
        const tocElement = config.tocElement;
        const titleElements = config.titleElements;

        let tocElementDiv = content.querySelector(tocElement);
        let tocUl = document.createElement("ul");
        tocUl.id = "list-toc-generated";
        tocElementDiv.appendChild(tocUl);

        // add class to all title elements
        let tocElementNbr = 0;
        for (var i = 0; i < titleElements.length; i++) {

            let titleHierarchy = i + 1;
            let titleElement = content.querySelectorAll(titleElements[i]);


            titleElement.forEach(function (element) {

                // add classes to the element
                element.classList.add("title-element");
                element.setAttribute("data-title-level", titleHierarchy);

                // add id if doesn't exist
                tocElementNbr++;
                idElement = element.id;
                if (idElement == '') {
                    element.id = 'title-element-' + tocElementNbr;
                }
            });
        }

        // create toc list
        let tocElements = content.querySelectorAll(".title-element");

        for (var i = 0; i < tocElements.length; i++) {
            let tocElement = tocElements[i];

            let tocNewLi = document.createElement("li");

            // Add class for the hierarcy of toc
            tocNewLi.classList.add("toc-element");
            tocNewLi.classList.add("toc-element-level-" + tocElement.dataset.titleLevel);

            // Keep class of title elements
            let classTocElement = tocElement.classList;
            for (var n = 0; n < classTocElement.length; n++) {
                if (classTocElement[n] != "title-element") {
                    tocNewLi.classList.add(classTocElement[n]);
                }
            }

            // Create the element
            tocNewLi.innerHTML = '<a href="#' + tocElement.id + '">' + tocElement.innerHTML + '</a>';
            tocUl.appendChild(tocNewLi);
        }
    }

    class TableOfContents extends Paged.Handler {
        beforeParsed(content) {
            // generate TOC
            if (typeof window.parent.createToc === 'function') {
                window.parent.createToc({
                    content: content,
                    tocElement: '#my-toc-content',
                    titleElements: ['h4.section-title', 'h5.section-title']
                });
            }
        }
    }

    Paged.registerHandlers(TableOfContents);

</script>
