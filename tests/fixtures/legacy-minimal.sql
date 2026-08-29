--
-- Minimal legacy dump fixture for importer tests
--

COPY public.trucks (id, truck_name, notes, created_at, truck_cost, units, arrival_date) FROM stdin;
1	Fixture Truck	\N	2026-01-01 00:00:00	1000.00	1	2026-01-01
\.

COPY public.models (id, model_number, product_name, brand, category, msrp, created_at, updated_at, variations) FROM stdin;
1	TESTMODEL	Test Product	BrandX	Washers	499.00	2026-01-01 00:00:00	2026-01-01 00:00:00	[]
\.

COPY public.truck_items (id, truck_id, category, model_number, product_name, brand, quantity, price, added_at, serial_number, receiving_condition, shipping_damage, shipping_damage_description, msrp, fuel_type, triage_date, triage_tech_id, initial_triage_condition, essential_parts, non_essential_parts, total_parts_cost, unit_label, red_dot, photos_json, current_status, total_cost, arrival_date, labor_hours, location, sold_price, sold_date, sold_by, subcategory, original_order_number, return_reason, return_problems) FROM stdin;
1	1	Washers	TESTMODEL	Test Product	BrandX	1	10.00	2026-01-01 00:00:00	SN001	A-Grade	f	\N	499.00	\N	\N	\N	\N	\N	\N	0.00	FT-001-001	f	[]	Triage	0.00	2026-01-01 00:00:00	0	\N	\N	\N	\N	\N	\N	\N	\N
\.

COPY public.users (id, username, password, email, role, platform, supabase_auth_id, must_change_password) FROM stdin;
7	WillFlory	hash	will@example.com	admin	\N	\N	f
\.
