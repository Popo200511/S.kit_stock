import './bootstrap';

import AOS from 'aos';
import 'aos/dist/aos.css';

// ใช้เฉพาะหน้าที่ใส่ data-aos="..." ไว้จริงๆ (ตอนนี้คือหน้าร้านค้าออนไลน์ /shop เท่านั้น) —
// import ไว้ตัวเดียวใช้ร่วมกันทั้งแอปได้ เพราะถ้าไม่มี data-aos บน element ก็ไม่มีผลอะไรเลย
AOS.init({ duration: 500, once: true, offset: 40 });

// หน้าเว็บนี้เปลี่ยนเนื้อหาแบบ SPA ผ่าน Livewire wire:navigate (ไม่ reload ทั้งหน้า) —
// ต้องสั่ง AOS ให้ไปสแกนหา element ใหม่ๆ ที่เพิ่งโผล่มาอีกรอบ ไม่งั้นหน้าที่ navigate มาใหม่
// จะไม่มี animation เลยเพราะ AOS สแกนไปแล้วตอน init ครั้งแรกก่อนที่ element พวกนี้จะมีอยู่
document.addEventListener('livewire:navigated', () => AOS.refreshHard());

// Printing #doc-sheet in place (nested under the sidebar/table layout) was
// unreliable across browsers — moving it to be a direct child of <body> right
// before printing sidesteps every containing-block/overflow/pagination quirk in
// one go, then it's moved back to its original spot afterwards. Defined here
// (not an inline <script> in the Livewire view) so it survives Livewire's DOM
// morphing/navigate — inline scripts injected via a morph don't auto-execute.
window.printDocSheet = function () {
    var sheet = document.getElementById('doc-sheet');
    if (!sheet) {
        window.print();
        return;
    }
    var parent = sheet.parentNode;
    var next = sheet.nextSibling;
    document.body.appendChild(sheet);
    window.print();
    if (next) {
        parent.insertBefore(sheet, next);
    } else {
        parent.appendChild(sheet);
    }
};
