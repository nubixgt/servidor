import { env } from 'cloudflare:workers';
const json = (body:unknown,status=200) => Response.json(body,{status,headers:{'Cache-Control':'no-store'}});
export async function GET() {
  try { const data=await env.DB!.prepare('SELECT table_id AS tableId, booking_id AS bookingId, name, created_at AS createdAt FROM reservations ORDER BY created_at DESC').all();return json(data.results); }
  catch {return json({error:'No se pudo consultar la disponibilidad. Intenta actualizar.'},503);}
}
export async function POST(request:Request) {
  let input;
  try {input=await request.json() as {tableIds:unknown;name:unknown};} catch{return json({error:'Solicitud inválida.'},400);}
  const {tableIds,name}=input;
  if(!Array.isArray(tableIds)||!tableIds.length||tableIds.length>90||new Set(tableIds).size!==tableIds.length||tableIds.some(id=>!Number.isInteger(id)||id<1||id>90)||typeof name!=='string'||name.trim().length<2||name.trim().length>100)return json({error:'Ingresa un nombre y selecciona al menos una mesa.'},400);
  const bookingId=crypto.randomUUID(),createdAt=new Date().toISOString();
  try {
    await env.DB!.batch(tableIds.map(id=>env.DB!.prepare('INSERT INTO reservations (table_id, booking_id, name, created_at) VALUES (?, ?, ?, ?)').bind(id,bookingId,name.trim(),createdAt)));
    return json({bookingId,tableIds,createdAt},201);
  } catch(e) {
    if(String(e).includes('UNIQUE')||String(e).includes('constraint')) return json({error:'Una de las mesas acaba de ser ocupada. Actualizamos el mapa para que elijas otra.'},409);
    return json({error:'No se pudo guardar la reserva. Intenta nuevamente.'},503);
  }
}
export async function DELETE(request:Request) {
  let id;
  try{id=(await request.json() as {bookingId:unknown}).bookingId;}catch{return json({error:'Solicitud inválida.'},400);}
  if(typeof id!=='string'||!/^[a-f0-9-]{36}$/.test(id))return json({error:'Reserva inválida.'},400);
  try {const result=await env.DB!.prepare('DELETE FROM reservations WHERE booking_id = ?').bind(id).run();return result.meta.changes?json({ok:true}):json({error:'La reserva ya no existe.'},404);}
  catch{return json({error:'No se pudo cancelar la reserva.'},503);}
}
