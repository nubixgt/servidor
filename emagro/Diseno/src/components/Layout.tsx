import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';

export default function Layout() {
  return (
    <div className="bg-[#F8F9FA] font-sans text-[#1F2937] antialiased h-screen flex overflow-hidden">
      <Sidebar />
      <div className="flex-1 flex flex-col h-full overflow-hidden relative">
        <Outlet />
      </div>
    </div>
  );
}
