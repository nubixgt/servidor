import React from "react";

export default function BackgroundDecoration() {
  return (
    <div className="absolute top-0 right-0 w-full h-[614px] md:w-1/2 md:h-screen pointer-events-none opacity-30 md:opacity-50 overflow-hidden z-0">
      <img
        alt="Premium Event Scene"
        className="w-full h-full object-cover object-center transition-all duration-1000 ease-out scale-105"
        src="https://lh3.googleusercontent.com/aida-public/AB6AXuBf-te7Jx8lFHnda-WiD_84QAIaiT4YyIPWEzclFHKDavGE0bI6_UaUAwYumD_jhr3rPCxBwymYe2VHHKFb_y0jgg1k2ZTSx_QDNtcXOUJQxIAdKpIO0U4ww7Y4SRTb-IpJCYmvlnMDths50SbIivZg16kaqcKoQ4L1s8LOEgyrmVdHlHjp1Fn6c9rAvYHzQbHpsVVMO4rQxWRpygU-_abcl4FXnhOTyHsKRaPnQb1Ifn0gXjnCkSVeE123-BKHw3tvtnhxxMjfsO3R"
        referrerPolicy="no-referrer"
      />
      {/* Dynamic atmospheric ambient lighting overlay */}
      <div className="absolute inset-0 bg-gradient-to-b from-[#0a0f1e]/10 via-[#0a0f1e]/80 to-[#0a0f1e]"></div>
      <div className="absolute inset-0 bg-gradient-to-r from-[#0a0f1e] via-[#0a0f1e]/30 to-transparent hidden md:block"></div>
      
      {/* Light glow effects */}
      <div className="absolute top-1/4 right-1/4 w-64 h-64 bg-primary-gold/5 rounded-full blur-3xl pointer-events-none"></div>
    </div>
  );
}
