export type Category = 'platino' | 'vip' | 'preferencia';
export const categories: Record<Category, {name: string; price: number; note: string}> = {
  platino: {name:'Platino',price:1600,note:'Junto a la pista'},
  vip: {name:'VIP',price:1400,note:'Una gran ubicación'},
  preferencia: {name:'Preferencia',price:1200,note:'Disfruta a tu manera'},
};
export const money = (n: number) => 'Q' + n.toLocaleString('en-US');
export type Table = {id:number;x:number;y:number;category:Category};
export const tables: Table[] = [];
const add = (id:number,x:number,y:number,category:Category) => tables.push({id,x,y,category});
add(1,242,229,'platino');add(2,307,229,'platino');
add(5,177,310,'platino');add(4,242,310,'platino');add(3,307,310,'platino');
[6,7,8].forEach((id,i)=>add(id,79,455+i*77,'vip'));
for(let row=0;row<6;row++) for(let col=0;col<5;col++) add(9+row*5+col,214+col*85,452+row*58,row===0?'platino':row<3?'vip':'preferencia');
for(let row=0;row<6;row++) for(let col=0;col<6;col++) add(69-row*6+col,790+col*85,452+row*58,row===0?(col<4?'platino':'vip'):row<3?'vip':'preferencia');
const rightRows=[[0,89,90],[86,87,88],[83,84,85],[80,81,82],[77,78,79],[75,76,0]];
rightRows.forEach((row,r)=>row.forEach((id,c)=>{if(id)add(id,1047+c*85,125+r*47,c===0?'platino':c===1?'vip':'preferencia')}));
tables.sort((a,b)=>a.id-b.id);
