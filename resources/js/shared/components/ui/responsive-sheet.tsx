import * as React from "react"

import {
  Drawer,
  DrawerClose,
  DrawerContent,
  DrawerDescription,
  DrawerTitle,
  DrawerTrigger,
} from "@/components/ui/drawer"
import {
  Sheet as BaseSheet,
  SheetClose as BaseSheetClose,
  SheetContent as BaseSheetContent,
  SheetDescription as BaseSheetDescription,
  SheetFooter as BaseSheetFooter,
  SheetHeader as BaseSheetHeader,
  SheetTitle as BaseSheetTitle,
  SheetTrigger as BaseSheetTrigger,
} from "@/components/ui/sheet"
import { useIsMobile } from "@/hooks/use-mobile"
import { cn } from "@/lib/utils"

/**
 * Drop-in replacement for the Sheet primitives: on desktop it keeps the requested
 * side; on phones it becomes an iOS-style bottom drawer (drag to dismiss).
 */
const PhoneContext = React.createContext(false)

function Sheet({
  children,
  ...props
}: React.ComponentProps<typeof BaseSheet>) {
  const isPhone = useIsMobile()

  return (
    <PhoneContext.Provider value={isPhone}>
      {isPhone ? (
        <Drawer
          open={props.open}
          defaultOpen={props.defaultOpen}
          onOpenChange={props.onOpenChange}
        >
          {children}
        </Drawer>
      ) : (
        <BaseSheet {...props}>{children}</BaseSheet>
      )}
    </PhoneContext.Provider>
  )
}

function SheetTrigger(props: React.ComponentProps<typeof BaseSheetTrigger>) {
  return React.useContext(PhoneContext) ? (
    <DrawerTrigger {...props} />
  ) : (
    <BaseSheetTrigger {...props} />
  )
}

function SheetContent({
  className,
  children,
  side,
  ...props
}: React.ComponentProps<typeof BaseSheetContent> & {
  side?: "top" | "right" | "bottom" | "left"
}) {
  if (React.useContext(PhoneContext)) {
    return (
      <DrawerContent>
        <div
          className={cn(
            "flex min-h-0 flex-1 flex-col overflow-y-auto pb-[env(safe-area-inset-bottom)]",
            className
          )}
        >
          {children}
        </div>
      </DrawerContent>
    )
  }

  return (
    <BaseSheetContent side={side} className={className} {...props}>
      {children}
    </BaseSheetContent>
  )
}

function SheetHeader({ className, ...props }: React.ComponentProps<"div">) {
  return <BaseSheetHeader className={cn(className)} {...props} />
}

function SheetFooter(props: React.ComponentProps<"div">) {
  return <BaseSheetFooter {...props} />
}

function SheetTitle(props: React.ComponentProps<typeof BaseSheetTitle>) {
  return React.useContext(PhoneContext) ? (
    <DrawerTitle {...props} />
  ) : (
    <BaseSheetTitle {...props} />
  )
}

function SheetDescription(
  props: React.ComponentProps<typeof BaseSheetDescription>
) {
  return React.useContext(PhoneContext) ? (
    <DrawerDescription {...props} />
  ) : (
    <BaseSheetDescription {...props} />
  )
}

function SheetClose(props: React.ComponentProps<typeof BaseSheetClose>) {
  return React.useContext(PhoneContext) ? (
    <DrawerClose {...props} />
  ) : (
    <BaseSheetClose {...props} />
  )
}

export {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
}
