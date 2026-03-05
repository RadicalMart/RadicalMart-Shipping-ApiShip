# RadicalMart Shipping: ApiShip

**RadicalMart Shipping: ApiShip** is a shipping integration plugin that connects RadicalMart with the **ApiShip delivery platform**.

The plugin acts as an adapter between RadicalMart’s shipping system and the ApiShip API.
It retrieves delivery options, calculates shipping costs, and transfers shipment information between the store and the external service.

---

## Purpose

This plugin provides **shipping calculation and delivery integration** through ApiShip.

Its responsibility is limited to:

- requesting shipping rates from ApiShip,
- presenting available delivery methods,
- transferring shipment information,
- synchronizing delivery-related data.

Order lifecycle management and business rules remain controlled by RadicalMart core.

---

## What this plugin does

- Integrates RadicalMart with the ApiShip API.
- Requests shipping rates based on order parameters.
- Returns available delivery services and pricing.
- Supports shipment-related data exchange with ApiShip.
- Allows RadicalMart orders to use ApiShip delivery methods.

---

## What this plugin does NOT do

- ❌ Does not create or manage orders
- ❌ Does not control order lifecycle logic
- ❌ Does not manage inventory
- ❌ Does not implement delivery business rules outside RadicalMart

The plugin functions as a **shipping service adapter**.

---

## Architecture role

Within RadicalMart shipping architecture:

```

RadicalMart Order
↓
Shipping System
↓
Shipping Plugin (ApiShip)
↓
External Delivery API

```

This plugin represents a **shipping gateway integration**.

---

## Shipping flow

1. RadicalMart prepares order data for shipping calculation.
2. The ApiShip plugin sends a request to the ApiShip API.
3. ApiShip returns available delivery services and rates.
4. RadicalMart displays available shipping options.
5. The selected delivery method is stored with the order.

---

## Configuration

The plugin exposes parameters required to connect with ApiShip, including:

- API credentials
- delivery calculation settings
- service configuration

All parameters are configured through standard Joomla plugin settings.

---

## Usage

This plugin is intended for:

- stores using ApiShip for logistics aggregation
- projects requiring automated shipping rate calculation
- installations integrating multiple delivery providers through ApiShip

The plugin can operate alongside other shipping plugins.

---

## Extensibility

Shipping behavior can be extended by:

- listening to RadicalMart shipping events
- implementing additional shipping plugins
- adding custom delivery calculation logic

The plugin follows RadicalMart’s event-driven architecture.