
const fullToolIcons = [
    {
        label: 'bold',
        type: '',
        svg: '<svg viewBox="0 0 18 18"><path class="im-stroke" d="M5,4H9.5A2.5,2.5,0,0,1,12,6.5v0A2.5,2.5,0,0,1,9.5,9H5A0,0,0,0,1,5,9V4A0,0,0,0,1,5,4Z"></path><path class="im-stroke" d="M5,9h5.5A2.5,2.5,0,0,1,13,11.5v0A2.5,2.5,0,0,1,10.5,14H5a0,0,0,0,1,0,0V9A0,0,0,0,1,5,9Z"></path></svg>'
    },
    {
        label: 'strike',
        type: '',
        svg: '<svg viewBox="0 0 18 18"><line class="im-stroke im-thin" x1="15.5" x2="2.5" y1="8.5" y2="9.5"></line><path class="im-fill" d="M9.007,8C6.542,7.791,6,7.519,6,6.5,6,5.792,7.283,5,9,5c1.571,0,2.765.679,2.969,1.309a1,1,0,0,0,1.9-.617C13.356,4.106,11.354,3,9,3,6.2,3,4,4.538,4,6.5a3.2,3.2,0,0,0,.5,1.843Z"></path><path class="im-fill" d="M8.984,10C11.457,10.208,12,10.479,12,11.5c0,0.708-1.283,1.5-3,1.5-1.571,0-2.765-.679-2.969-1.309a1,1,0,1,0-1.9.617C4.644,13.894,6.646,15,9,15c2.8,0,5-1.538,5-3.5a3.2,3.2,0,0,0-.5-1.843Z"></path></svg>'
    },
    {
        label: 'italic',
        type: '',
        svg: '<svg viewBox="0 0 18 18"><line class="im-stroke" x1="7" x2="13" y1="4" y2="4"></line><line class="im-stroke" x1="5" x2="11" y1="14" y2="14"></line><line class="im-stroke" x1="8" x2="10" y1="14" y2="4"></line></svg>'
    },
    {
        label: 'underline',
        type: '',
        svg: '<svg viewBox="0 0 18 18"><path class="im-stroke" d="M5,3V9a4.012,4.012,0,0,0,4,4H9a4.012,4.012,0,0,0,4-4V3"></path><rect class="im-fill" height="1" rx="0.5" ry="0.5" width="12" x="3" y="15"></rect></svg>'
    },
    {
        label: 'blockquote',
        type: '',
        svg: '<svg viewBox="0 0 18 18"><rect class="im-fill im-stroke" height="3" width="3" x="4" y="5"></rect><rect class="im-fill im-stroke" height="3" width="3" x="11" y="5"></rect><path class="im-even im-fill im-stroke" d="M7,8c0,4.031-3,5-3,5"></path><path class="im-even im-fill im-stroke" d="M14,8c0,4.031-3,5-3,5"></path></svg>'
    },
    {
        label: 'link',
        type: '',
        svg: '<svg viewBox="0 0 18 18"><line class="im-stroke" x1="7" x2="11" y1="7" y2="11"></line><path class="im-even im-stroke" d="M8.9,4.577a3.476,3.476,0,0,1,.36,4.679A3.476,3.476,0,0,1,4.577,8.9C3.185,7.5,2.035,6.4,4.217,4.217S7.5,3.185,8.9,4.577Z"></path><path class="im-even im-stroke" d="M13.423,9.1a3.476,3.476,0,0,0-4.679-.36,3.476,3.476,0,0,0,.36,4.679c1.392,1.392,2.5,2.542,4.679.36S14.815,10.5,13.423,9.1Z"></path></svg>'
    },
    {
        label: 'list',
        type: 'ordered',
        svg: '<svg viewBox="0 0 18 18"><line class="im-stroke" x1="7" x2="15" y1="4" y2="4"></line><line class="im-stroke" x1="7" x2="15" y1="9" y2="9"></line><line class="im-stroke" x1="7" x2="15" y1="14" y2="14"></line><line class="im-stroke im-thin" x1="2.5" x2="4.5" y1="5.5" y2="5.5"></line><path class="im-fill" d="M3.5,6A0.5,0.5,0,0,1,3,5.5V3.085l-0.276.138A0.5,0.5,0,0,1,2.053,3c-0.124-.247-0.023-0.324.224-0.447l1-.5A0.5,0.5,0,0,1,4,2.5v3A0.5,0.5,0,0,1,3.5,6Z"></path><path class="im-stroke im-thin" d="M4.5,10.5h-2c0-.234,1.85-1.076,1.85-2.234A0.959,0.959,0,0,0,2.5,8.156"></path><path class="im-stroke im-thin" d="M2.5,14.846a0.959,0.959,0,0,0,1.85-.109A0.7,0.7,0,0,0,3.75,14a0.688,0.688,0,0,0,.6-0.736,0.959,0.959,0,0,0-1.85-.109"></path></svg>'
    },
    {
        label: 'list',
        type: 'bullet',
        svg: '<svg viewBox="0 0 18 18"><line class="im-stroke" x1="6" x2="15" y1="4" y2="4"></line><line class="im-stroke" x1="6" x2="15" y1="9" y2="9"></line><line class="im-stroke" x1="6" x2="15" y1="14" y2="14"></line><line class="im-stroke" x1="3" x2="3" y1="4" y2="4"></line><line class="im-stroke" x1="3" x2="3" y1="9" y2="9"></line><line class="im-stroke" x1="3" x2="3" y1="14" y2="14"></line></svg>'
    },
    {
        label: 'list',
        type: 'unchecked',
        svg: '<svg viewBox="0 0 18 18"><line class="im-stroke" x1="9" x2="15" y1="4" y2="4"></line><polyline class="im-stroke" points="3 4 4 5 6 3"></polyline><line class="im-stroke" x1="9" x2="15" y1="14" y2="14"></line><polyline class="im-stroke" points="3 14 4 15 6 13"></polyline><line class="im-stroke" x1="9" x2="15" y1="9" y2="9"></line><polyline class="im-stroke" points="3 9 4 10 6 8"></polyline></svg>'
    },
]

export {fullToolIcons}
