export interface Lottery {
  id: number;
  name: string;
  description?: string;
  type_name: string;
  status: string;
  price: number;
  drawn_amount: number;
  participants: number;
  last_draw_at?: string;
  next_draw_at?: string;
  draw_date?: string;
  end_date?: string;
  image_url?: string;
}

export interface LotteryType {
  id: number;
  name: string;
  description?: string;
  color?: string;
  icon?: string;
}

export interface Ticket {
  id?: number;
  ticket_number: string;
  price: number;
  lottery_id: number;
  status?: 'available' | 'reserved' | 'purchased';
  created_at?: string;
}

export interface UserTicket {
  id: number;
  lottery_id: number;
  ticket_id: number;
  ticket_number: string;
  purchased_at: string;
  lottery_name?: string;
  draw_date?: string;
  is_drawn?: boolean;
  lottery_type_name?: string;
  lottery_type_id?: number;
  win_amount?: number | null;
  winner_position?: number | null;
  winning_currency_code?: string;
  winning_currency_name?: string;
  status: 'active' | 'drawn' | 'winner' | 'history';
}

export interface UserTicketDetailed extends UserTicket {
  lottery: Lottery;
  winning_amount?: number;
  is_winner: boolean;
  leaderboard?: LeaderboardEntry[];
}

export interface LeaderboardEntry {
  position: number;
  ticket_number: string;
  user_name?: string;
  prize_amount: number;
}

export interface CartItem {
  id?: string;
  ticket_number: string;
  lottery_id: number;
  lottery_name?: string;
  price: number;
  quantity?: number;
}

export interface Cart {
  items: CartItem[];
  total_price: number;
  total_items: number;
}

export interface ApiResponse<T> {
  success: boolean;
  status: string;
  data: T;
  errors?: string[];
  message?: string;
}

export interface LotteriesResponse {
  success: boolean;
  data: Lottery[];
}

export interface UserStats {
  total_tickets: number;
  active_tickets: number;
  total_winnings: number;
  biggest_win: number;
  tickets_this_month: number;
} 