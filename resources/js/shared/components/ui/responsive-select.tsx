import { Check, ChevronDown } from "lucide-react"
import * as React from "react"

import {
  Drawer,
  DrawerContent,
  DrawerDescription,
  DrawerTitle,
} from "@/components/ui/drawer"
import {
  Select as BaseSelect,
  SelectContent as BaseSelectContent,
  SelectItem as BaseSelectItem,
  SelectTrigger as BaseSelectTrigger,
  SelectValue as BaseSelectValue,
} from "@/components/ui/select"
import { useIsMobile } from "@/hooks/use-mobile"
import { cn } from "@/lib/utils"

type Item = { value: string; label: string; disabled?: boolean }

type PhoneState = {
  items: Item[]
  value: string | undefined
  placeholder: string | undefined
  disabled: boolean
  open: boolean
  setOpen: (open: boolean) => void
  select: (value: string) => void
}

const PhoneContext = React.createContext<PhoneState | null>(null)

function textOf(node: React.ReactNode): string {
  if (typeof node === "string" || typeof node === "number") {
    return String(node)
  }

  if (Array.isArray(node)) {
    return node.map(textOf).join("")
  }

  if (React.isValidElement<{ children?: React.ReactNode }>(node)) {
    return textOf(node.props.children)
  }

  return ""
}

function collectItems(node: React.ReactNode, found: Item[] = []): Item[] {
  React.Children.forEach(node, (child) => {
    if (!React.isValidElement<{ children?: React.ReactNode; value?: string; disabled?: boolean }>(child)) {
      return
    }

    if (child.type === SelectItem) {
      found.push({
        value: String(child.props.value),
        label: textOf(child.props.children),
        disabled: child.props.disabled,
      })

      return
    }

    collectItems(child.props.children, found)
  })

  return found
}

function findPlaceholder(node: React.ReactNode): string | undefined {
  let placeholder: string | undefined

  React.Children.forEach(node, (child) => {
    if (!React.isValidElement<{ children?: React.ReactNode; placeholder?: string }>(child)) {
      return
    }

    if (child.type === SelectValue) {
      placeholder = child.props.placeholder
    } else {
      placeholder = placeholder ?? findPlaceholder(child.props.children)
    }
  })

  return placeholder
}

/**
 * Drop-in for the Select primitives: the Radix popover on desktop, and on phones a
 * trigger that opens an iOS-style bottom list.
 */
function Select({
  children,
  value,
  defaultValue,
  onValueChange,
  disabled,
  ...props
}: React.ComponentProps<typeof BaseSelect>) {
  const isPhone = useIsMobile()
  const [inner, setInner] = React.useState(defaultValue)
  const [open, setOpen] = React.useState(false)

  if (!isPhone) {
    return (
      <BaseSelect
        value={value}
        defaultValue={defaultValue}
        onValueChange={onValueChange}
        disabled={disabled}
        {...props}
      >
        {children}
      </BaseSelect>
    )
  }

  const current = value ?? inner

  return (
    <PhoneContext.Provider
      value={{
        items: collectItems(children),
        value: current,
        placeholder: findPlaceholder(children),
        disabled: Boolean(disabled),
        open,
        setOpen,
        select: (next) => {
          setInner(next)
          onValueChange?.(next)
          setOpen(false)
        },
      }}
    >
      {children}
    </PhoneContext.Provider>
  )
}

function SelectTrigger({
  className,
  children,
  ...props
}: React.ComponentProps<typeof BaseSelectTrigger>) {
  const phone = React.useContext(PhoneContext)

  if (!phone) {
    return (
      <BaseSelectTrigger className={className} {...props}>
        {children}
      </BaseSelectTrigger>
    )
  }

  return (
    <button
      type="button"
      data-slot="select-trigger"
      data-size="default"
      disabled={phone.disabled}
      onClick={() => phone.setOpen(true)}
      aria-haspopup="listbox"
      className={cn(
        "border-input bg-background flex h-11 w-full items-center justify-between gap-2 rounded-xl border px-3 text-left text-base disabled:opacity-50",
        className
      )}
    >
      {children}
      <ChevronDown className="size-4 shrink-0 opacity-50" />
    </button>
  )
}

function SelectValue({
  placeholder,
  ...props
}: React.ComponentProps<typeof BaseSelectValue>) {
  const phone = React.useContext(PhoneContext)

  if (!phone) {
    return <BaseSelectValue placeholder={placeholder} {...props} />
  }

  const selected = phone.items.find((item) => item.value === phone.value)

  return (
    <span
      className={cn("line-clamp-1", !selected && "text-muted-foreground")}
    >
      {selected?.label ?? placeholder ?? "Select"}
    </span>
  )
}

function SelectContent({
  children,
  ...props
}: React.ComponentProps<typeof BaseSelectContent>) {
  const phone = React.useContext(PhoneContext)

  if (!phone) {
    return <BaseSelectContent {...props}>{children}</BaseSelectContent>
  }

  return (
    <Drawer open={phone.open} onOpenChange={phone.setOpen}>
      <DrawerContent>
        <div className="px-5 pt-2 pb-2">
          <DrawerTitle>{phone.placeholder ?? "Choose an option"}</DrawerTitle>
          <DrawerDescription className="sr-only">
            Choose an option
          </DrawerDescription>
        </div>
        <ul
          role="listbox"
          className="min-h-0 flex-1 overflow-y-auto px-3 pb-[calc(1rem+env(safe-area-inset-bottom))]"
        >
          {phone.items.map((item) => (
            <li key={item.value}>
              <button
                type="button"
                role="option"
                aria-selected={item.value === phone.value}
                disabled={item.disabled}
                onClick={() => phone.select(item.value)}
                className="flex min-h-12 w-full items-center justify-between gap-3 rounded-xl px-3 text-left text-base active:bg-muted disabled:opacity-50"
              >
                {item.label}
                {item.value === phone.value && (
                  <Check className="text-primary size-5" />
                )}
              </button>
            </li>
          ))}
        </ul>
      </DrawerContent>
    </Drawer>
  )
}

function SelectItem(props: React.ComponentProps<typeof BaseSelectItem>) {
  return <BaseSelectItem {...props} />
}

export { Select, SelectContent, SelectItem, SelectTrigger, SelectValue }
