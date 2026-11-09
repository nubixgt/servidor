import { sqliteTable, text, integer } from 'drizzle-orm/sqlite-core';
export const reservations = sqliteTable('reservations', {
  tableId: integer('table_id').primaryKey(),
  bookingId: text('booking_id').notNull(),
  name: text('name').notNull(),
  createdAt: text('created_at').notNull(),
});
